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

#[Route('/home')]
class HomePageController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_home_page')]
    public function index(ProductService $productService, CategoryService $categoryService): Response
    {
        //Récupération des nouveautés
        $newProducts = $productService->getRecentlyProductWithFavorites();
        
        return $this->render('home_page/index.html.twig', [
            'newProducts' => $newProducts,
        ]);
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

