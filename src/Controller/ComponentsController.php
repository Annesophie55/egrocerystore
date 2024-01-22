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


    // Appliquer les filtres
      if ($sort) {
        $queryBuilder->orderBy('p.price', $sort); // Exemple pour le tri par prix
    }

    if ($nutriScore) {
        $queryBuilder->andWhere('p.nutriScore = :nutriScore')
                     ->setParameter('nutriScore', $nutriScore);
    }

    if ($categoryInput) {
        $queryBuilder->andWhere('c.name = :category')
                     ->setParameter('category', $categoryInput);
    }

    // Obtenir les produits filtrés
    $productsData = $queryBuilder->getQuery()->getResult();



    // Préparer les données pour la réponse
    $products = [];
    foreach ($productsData as $product) {
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'nutriScore' => $productService->calculateNutriScore($product),
            'promotion' => null,
        ];


        if ($promotion = $product->getPromotion()) {
            $productData['promotion'] = [
                'rising' => $promotion->getRising(),
                // Ajouter d'autres champs si nécessaire
            ];
        }

        $products[] = $productData;
    }

    $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

    return $this->render('product/index.html.twig', [
        'products' => $products,
        'categoriesWithChildren' => $categoriesWithChildren,
    ]);
}

}
