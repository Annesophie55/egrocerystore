<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Nutrition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ImportProductsCommand extends Command
{
    protected static $defaultName = 'app:import-products';
    private $entityManager;
    private $container;

    public function __construct(EntityManagerInterface $entityManager,  ContainerBagInterface $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Importe des produits depuis l\'API Open Food Facts et les stocke en base de données.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Désactiver les contraintes de clé étrangère pour vider les tables sans conflits
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category')->execute();
        $this->entityManager->getConnection()->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $pageSize = 100;
        $totalPages = 5;
        $client = HttpClient::create();

        for ($page = 1; $page <= $totalPages; $page++) {
            $url = 'https://fr.openfoodfacts.org/cgi/search.pl?search_terms=&search_simple=1&json=1&page_size=' . $pageSize . '&page=' . $page . '&sort_by=random';
            $response = $client->request('GET', $url);
            $productData = $response->toArray();

            foreach ($productData['products'] as $productInfo) {
                if (isset($productInfo['product_name'], $productInfo['brands'])) {
                    $productName = $productInfo['product_name'];
                    $brandName = $productInfo['brands'];

                    // Créer un nouveau produit
                    $product = new Product();
                    $product->setName($productName . ' ' . $brandName);
                    $product->setDescription($productInfo['generic_name'] ?? '');
                    
                    $product->setImage(null); // Définir par défaut à null

                    if (isset($productInfo['image_url'])) {
                        // Télécharger et stocker l'image localement
                        $localImagePath = $this->downloadImage($productInfo['image_url']);
                        if ($localImagePath) {
                            $product->setImage($localImagePath); // Sauvegarder le chemin de l'image locale en base de données
                        }
                    }
                    

                    // Calcul du prix en fonction de la quantité
                    $quantity = $productInfo['quantity'] ?? '100 g';
                    $approxPrice = $this->calculatePrice($quantity);
                    $product->setPrice($approxPrice);
                    $product->setQuantity(10);

                    // Gestion des informations nutritionnelles
                    $nutrition = new Nutrition();
                    $nutriments = $productInfo['nutriments'];
                    $nutrition->setEnergy($nutriments['energy_100g'] ?? 0);
                    $nutrition->setFibers($nutriments['fiber_100g'] ?? 0);
                    $nutrition->setLipids($nutriments['fat_100g'] ?? 0);
                    $nutrition->setProteins($nutriments['proteins_100g'] ?? 0);
                    $nutrition->setSalt($nutriments['salt_100g'] ?? 0);
                    $nutrition->setSaturatedFattyAcid($nutriments['saturated-fat_100g'] ?? 0);
                    $nutrition->setCarbohydrates($nutriments['carbohydrates_100g'] ?? 0);
                    $nutrition->setSugar($nutriments['sugars_100g'] ?? 0);

                    $this->entityManager->persist($nutrition);
                    $product->setNutrition($nutrition);

                    // Gestion des catégories et des rayons
                    if (isset($productInfo['nutriments'])) {
                        // Obtenir ou créer les catégories/rayons avant d'ajouter au produit
                        $this->handleCategoriesAndRayons($product, $productInfo, $output);
                    }

                    // Persister le produit avec ses catégories et nutrition
                    $this->entityManager->persist($product);
                    $this->entityManager->flush(); // Sauvegarder après chaque produit
                }
            }
        }

        $output->writeln('Importation terminée !');
        return Command::SUCCESS;
    }

    private function handleCategoriesAndRayons(Product $product, array $productInfo, OutputInterface $output)
    {
        $rayon = $this->getRayonFromNutrients($productInfo['nutriments']);
    
        // Vérifier l'existence du rayon (catégorie parent) dans la BDD
        $mainCategory = $this->entityManager->getRepository(Category::class)->findOneBy([
            'name' => $rayon,
        ]);
    
        if (!$mainCategory) {
            // Si le rayon n'existe pas, le créer
            $mainCategory = new Category();
            $mainCategory->setName($rayon);
            $this->entityManager->persist($mainCategory);
        }
    
        // Ajouter le rayon (catégorie parent) au produit
        if (!$product->getCategories()->contains($mainCategory)) {
            $product->addCategory($mainCategory);
        }
    
        // Traiter les sous-catégories avec le préfixe "fr:"
        if (isset($productInfo['categories_tags'])) {
            foreach ($productInfo['categories_tags'] as $categoryName) {
                // Filtrer uniquement les catégories commençant par "fr:"
                if (strpos($categoryName, 'fr:') === 0) {
                    $cleanCategoryName = $this->cleanCategoryName($categoryName);
    
                    $subCategory = $this->entityManager->getRepository(Category::class)->findOneBy([
                        'name' => $cleanCategoryName,
                    ]);
    
                    if (!$subCategory) {
                        // Si la sous-catégorie n'existe pas, la créer et l'associer à son rayon
                        $subCategory = new Category();
                        $subCategory->setName($cleanCategoryName);
                        $subCategory->setParent($mainCategory); // Associer au rayon
                        $this->entityManager->persist($subCategory);
                        $output->writeln('Sous-catégorie ajoutée : ' . $cleanCategoryName . ' (Rayon : ' . $mainCategory->getName() . ')');
                    }
    
                    // Ajouter la sous-catégorie au produit
                    if (!$product->getCategories()->contains($subCategory)) {
                        $product->addCategory($subCategory);
                    }
                }
            }
        }
    }
    
    private function cleanCategoryName(string $categoryName): string
    {
        // Retirer le préfixe 'fr:' des noms de catégorie
        return trim(strtolower(str_replace('fr:', '', $categoryName)));
    }
    

    private function calculatePrice(string $quantity): float
    {
        if (strpos($quantity, 'kg') !== false) {
            return rand(5, 50);
        } elseif (strpos($quantity, 'l') !== false) {
            return rand(1, 10);
        } else {
            return rand(0.5, 5);
        }
    }

    private function getRayonFromNutrients(array $nutriments): string
    {
        $gras = $nutriments['fat_100g'] ?? 0;
        $sucre = $nutriments['sugars_100g'] ?? 0;
        $sel = $nutriments['salt_100g'] ?? 0;
        $protein = $protein['protein_100g'] ?? 0;
        $saturedFat = $nutriments['saturated-fat_100g'] ?? 0;
        $fiber = $nutriments['fiber_100g'] ?? 0;
        
        

        if ($gras > 20 && $saturedFat > 5) {
            return 'charcuterie';
        } elseif (($sucre > 15 && $fiber > 20)|| ($sucre > 10 && $gras > 10)) {
            return 'petit-déjeuner';
        } elseif ($sel > 0.5) {
            return 'épicerie salée';
       
        }
        elseif ($gras > 10 && $protein > 15) {
            return 'charcuterie';
        } else {
            return 'épicerie générale';
        }
    }

    private function downloadImage(string $url): ?string
    {
        try {
            //Créer un client HTTP pour télécharger l'image
            $client = HttpClient::create();
            $response = $client->request('GET', $url);

            //Vérifier que la requête a réussi
            if($response->getStatusCode() !==200) {
                return null;
            }

            //récuperer le contenu de l'image
            $imageData = $response->getContent();
             // Générer un nom unique pour l'image (md5 ou autre méthode)
            $imageName = md5(uniqid()) . '.jpg'; // Vous pouvez changer l'extension selon le format de l'image
            $imagePath = $this->container->get('kernel.project_dir') . '/public/images/products/' . $imageName;


        // Sauvegarder l'image localement
        file_put_contents($imagePath, $imageData);

        // Retourner le chemin relatif qui sera enregistré en BDD
        return '/images/products/' . $imageName;

        } catch (\Exception $e) {
            // En cas d'erreur, retourner null
            return null;
        }

    }
}
