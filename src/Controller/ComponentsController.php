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

    #[Route('/products/filters', name: 'app_products_filters', methods: ['GET', 'POST'])]
    public function filtersProducts(Request $request, ProductService $productService, ProductRepository $productRepository): Response {


    $sort = $request->request->get('sort');
    $nutriScore = $request->request->get('nutriScore');
    $categoryInput = $request->request->get('category'); 
    $page = $request->query->get('page', 1); 

    $queryBuilder = $productRepository->createQueryBuilder('p')
                                    ->leftJoin('p.nutrition', 'nutrition')
                                    ->leftJoin('p.categories', 'category');

        // dd($queryBuilder);
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

    $limit = 10;

    $offset =($page -1)* $limit;

    $query = $queryBuilder->setFirstResult($offset)
                        ->setMaxResults($limit)
                        ->getQuery();

    $paginator = new Paginator($query, $fetchJoinCollection = true);

    $products = [];
    foreach ($paginator as $product) {
        $products[] = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'nutriScore' => $productService->calculateNutriScore($product),
            'promotion' => $product->getPromotion() ? $product->getPromotion()->getRising() : null,
        ];

        $products[] = $product;
    }

    $totalProducts = count($paginator);
    $totalPages = ceil($totalProducts / $limit);

    $categoriesWithChildren = $this->categoryService->getTopLevelCategoriesWithChildren();

    return $this->render('product/index.html.twig', [
        'products' => $products,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'categoriesWithChildren' => $categoriesWithChildren,
        'routeName' => 'app_products_filters', // Le nom de la route utilisée pour la pagination
        'queryParameters' => $request->query->all(), // Tous les autres paramètres de requête
        'pageParameterName' => 'page', // Le nom du paramètre de requête pour le numéro de la page
    ]);
}

}
