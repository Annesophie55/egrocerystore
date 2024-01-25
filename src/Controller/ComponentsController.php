<?php

namespace App\Controller;

use App\Services\ProductService;
use App\Services\CategoryService;
use App\Repository\ProductRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ComponentsController extends AbstractController
{
    private $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    #[Route('/category/header', name: 'category_header')]
    public function categoryInHeader(): Response
    {
        $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

        return $this->render('partials/_header.html.twig', [
            'categoriesWithChildren' => $categoriesWithChildren,
        ]);
    }

    #[Route('/category/nav', name: 'category_nav')]
    public function categoryInNav(): Response
    {
        $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

        return $this->render('components/_categoryNav.html.twig', [
            'categoriesWithChildren' => $categoriesWithChildren,
        ]);
    }

}
