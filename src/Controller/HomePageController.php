<?php

namespace App\Controller;

use App\Entity\Product;
use Psr\Log\LoggerInterface;
use App\Services\ProductService;
use App\Repository\ProductRepository;
use App\Services\CategoryService;
use App\Services\PromotionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Length;

class HomePageController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_home_page')]
    public function index(ProductService $productService, PromotionService $promotionService): Response
    {
        //Vérifie si un utilisateur est connécté
        $user = $this->getUser();

        // Récupération des nouveautés
        $newProducts = $productService->getRecentlyProduct(6);

        $favoritesProducts = [];
        $boughtProducts = [];
        $smallPriceProducts = [];
        $smallPriceProducts = $productService->getSmallPrice();
        
        
        $productInPromotionForCarousel = $productService->getByPromotion(6);

        $promotionProducts = $productService->getByPromotion(6);

        if ($user && count($productService->getFavoritesProducts($user)) >= 4) {
            $favoritesProducts = $productService->getFavoritesProducts($user);
        }
        if ($user && count($productService->getBoughtProduct($user)) >= 4) {
            $boughtProducts = $productService->getBoughtProduct($user);
            $boughtProducts = array_slice($boughtProducts, 0, 8);
        }

        return $this->render('home_page/index.html.twig', [
            'newProducts' => $newProducts,
            'favoritesProducts' => $favoritesProducts,
            'boughtProducts' => $boughtProducts,
            'smallPriceProducts' => $smallPriceProducts,
            'productInPromotionForCarousel' => $productInPromotionForCarousel,
            'promotionProducts' => $promotionProducts
        ]);
 
    }

    #[Route('/profil/favorite/toggle/{id}', name: 'toggle_favorite', methods:'POST')]
    public function toggleFavorite(Product $product, ProductService $productService ): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour ajouter des favoris.');
        }

        // Vérifie si le produit est déjà un favori de l'utilisateur
        if ($productService->getFavoritesProducts($user)->contains($product)) {
            $productService->removeFavoriteProduct($user, $product);
            $status = 'removed';
        } else {
            $productService->addFavoriteProduct($user, $product);
            $status = 'added';
        }

        $this->entityManager->flush();

        $status = $this->json(['status' => $status]);

        return $status;
    }
}

