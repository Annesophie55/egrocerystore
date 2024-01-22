<?php

// src/Controller/HomePageController.php

namespace App\Controller;

use App\Entity\Product;
use Psr\Log\LoggerInterface;
use App\Services\ProductService;
use App\Repository\ProductRepository;
use App\Services\CategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class HomePageController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_home_page')]
    public function index(ProductService $productService, CategoryService $categoryService, ProductRepository $productRepository): Response
    {
        //Vérifie si un utilisateur est connécté
        $user = $this->getUser();
        //Si oui je récupère la liste de ses favoris et de ses derniers achats
        if ($user) {
            // Récupération des favoris de l'utilisateur
            $favorites = $user->getFavorite()->toArray();
        
            // Transformation des favoris en tableau avec les données nécessaires pour l'affichage
            $favoritesData = array_map(function ($favoriteProduct) use ($productService) {
                // Assurez-vous que $favoriteProduct est un objet Product
                return [
                    'product' => $favoriteProduct,
                    'promotion' => $favoriteProduct->getPromotion(),
                    'nutriScore' => $productService->calculateNutriScore($favoriteProduct),
                ];
            }, $favorites);
        
            // Récupération des commandes et des produits achetés
            $orders = $user->getOrder(); 
            $ordersArray = $orders->toArray();
        
            $buyProductsData = [];

            if ($ordersArray) {

                foreach ($ordersArray as $order) {

                    $orderItems = $order->getOrderItems();

                    foreach ($orderItems as $orderItem) {

                        $product = $orderItem->getProduct();

                        if ($product) {
                            $buyProductsData[] = [
                                'product' => $product,
                                'promotion' => $product->getPromotion(),
                                'nutriScore' => $productService->calculateNutriScore($product),
                            ];
                        }
                    }
                }
            }
        
            // Récupération des nouveautés
            $newProducts = $productService->getRecentlyProduct(16);
        
            return $this->render('home_page/index.html.twig', [
                'newProducts' => $newProducts,
                'favoritesData' => $favoritesData,
                'buyProductsData' => $buyProductsData,
            ]);
        }
    }

    #[Route('/profil/favorite/toggle/{id}', name: 'toggle_favorite', methods:'POST')]
    public function toggleFavorite(Product $product): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour ajouter des favoris.');
        }

        // Vérifie si le produit est déjà un favori de l'utilisateur
        if ($user->getFavorite()->contains($product)) {
            // Si oui, le retirer des favoris
            $user->removeFavorite($product);
            $status = 'removed';
        } else {
            // Si non, l'ajouter aux favoris
            $user->addFavorite($product);
            $status = 'added';
        }

        $this->entityManager->flush();

        $status = $this->json(['status' => $status]);

        // Retourne un statut JSON indiquant le succès ou l'échec
        return $status;
    }
}

