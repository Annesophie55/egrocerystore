<?php

namespace App\Controller;

use App\Services\ProductService;
use App\Services\CategoryService;
use App\Repository\ProductRepository;
use App\Services\CartService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ComponentsController extends AbstractController
{
    private $categoryService;
    private $session;

    public function __construct(CategoryService $categoryService,  RequestStack $requestStack)
    {
        $this->categoryService = $categoryService;
        $this->session = $requestStack->getSession();
    }
    #[Route('/category/header', name: 'category_header')]
    public function categoryInHeader(CartService $cartService, ProductRepository $productRepository): Response
    {
        $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

        $cartQuantity = $cartService->getCartQuantity();


        return $this->render('partials/_header.html.twig', [
            'categoriesWithChildren' => $categoriesWithChildren,
            'cartQuantity' => $cartQuantity
        ]);
    }

    #[Route('/category/cart/nav', name: 'category_nav')]
    public function categoryInNav(): Response
    {
        $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

        return $this->render('components/_categoryNav.html.twig', [
            'categoriesWithChildren' => $categoriesWithChildren,
        ]);
    }

}
