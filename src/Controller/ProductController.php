<?php

namespace App\Controller;

use App\Services\ProductService;
use App\Services\CategoryService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/product')]
class ProductController extends AbstractController
{
    private $productService;
    private $categoryService;

    public function __construct(CategoryService $categoryService,ProductService $productService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    #[Route('/', name: 'app_product')]
    public function index(): Response
    {
        $products = $this->productService->getProducts();
     
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

// #[Route('/filtersProducts', name: 'app_home_product_filters', methods: "POST")]
// public function filtersProducts(Request $request, ProductService $productService): JsonResponse {
//     // Récupérer les filtres de la requête
//     $sort = $request->get('sort');
//     $category = $request->get('category');
//     $priceMax = $request->get('price_max');
//     $priceRange = $request->get('price_range');
//     $nutriScore = $request->get('nutriScore');

//     // Utiliser ProductService pour obtenir les produits filtrés
//     $filteredProducts = $productService->getFilteredProducts($sort, $category, $priceMax, $priceRange, $nutriScore);

//     // Préparer les données pour la réponse JSON
//     $responseData = [];
//     foreach ($filteredProducts as $product) {
//         $productData = [
//             'id' => $product->getId(),
//             'name' => $product->getName(),
//             'description' => $product->getDescription(),
//             'price' => $product->getPrice(),
//             'nutriScore' => $productService->calculateNutriScore($product),
//             'promotion' => null,
//         ];

//         // Ajouter les informations de promotion si disponibles
//         if ($promotion = $product->getPromotion()) {
//             $productData['promotion'] = [
//                 'description' => $promotion->getDescription(),
//                 'discount' => $promotion->getDiscount(),
//                 // Ajouter d'autres champs si nécessaire
//             ];
//         }

//         $responseData[] = $productData;
//     }

//     return new JsonResponse(['products' => $responseData]);
// }


    #[Route('/details/{product_id}', name:'app_product_details', methods:'GET')]
    public function productDetails($product_id)
    {

        $product = $this->productService->getOneProduct($product_id);

        return $this->render('product/details.html.twig',[
            'product' => $product,
        ]);
    }

    #[Route('/category/{category_id}', name: 'app_product_category', methods: 'GET')]
    public function productByCategory($category_id)
    {

        $products = $this->productService->getProductsBycategory($category_id);

        return $this->render('product/category.html.twig', [
            'products' => $products,
        ]);
    }
}
