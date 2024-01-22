<?php

namespace App\Controller;

use App\Services\ProductService;
use App\Services\CategoryService;
use App\Repository\ProductRepository;
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

    #[Route('/products/filters', name: 'app_products_filters', methods: ['GET', 'POST'])]
public function filtersProducts(Request $request, ProductService $productService, ProductRepository $productRepository): Response {

    // Construire la requête de base
    $queryBuilder = $productRepository->createQueryBuilder('p')
                                      ->leftJoin('p.categories', 'c');

    $sort = $request->request->get('sort');
    $nutriScore = $request->request->get('nutriScore');
    $categoryInput = $request->request->get('category'); 

    $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();


    // var_dump($sort);
    // var_dump($nutriScore);
    // var_dump($categoryInput);

    // $logger->info('Category Input', ['categoryInput' => $categoryInput]);

    // if ($categoryInput) {
    //     $productRepository->filterByCategory($categoryInput);
    // }
    // if ($sort) {
    //     $productRepository->filterBySort($sort, $queryBuilder);
    // }
    // if ($nutriScore) {
    //     $productRepository->filterByNutriscore($nutriScore, $queryBuilder);
    // }

    // $filteredProducts = $queryBuilder->getQuery()->getResult();

    // var_dump($filteredProducts);



    // Préparer les données pour la réponse
    // $products = [];
    // foreach ($filteredProducts as $product) {
    //     $productData = [
    //         'id' => $product->getId(),
    //         'name' => $product->getName(),
    //         'description' => $product->getDescription(),
    //         'price' => $product->getPrice(),
    //         'nutriScore' => $productService->calculateNutriScore($product),
    //         'promotion' => null,
    //     ];

    //     var_dump($productData);


    //     if ($promotion = $product->getPromotion()) {
    //         $productData['promotion'] = [
    //             'rising' => $promotion->getRising(),
    //             // Ajouter d'autres champs si nécessaire
    //         ];
    //     }

    //     $products[] = $productData;
    // }

    return $this->render('components/_filtersForm.html.twig', [
        'categoriesWithChildren' => $categoriesWithChildren,
    ]);
}
}
