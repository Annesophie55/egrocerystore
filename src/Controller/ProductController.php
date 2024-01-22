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

    #[Route('/bought', name: 'app_bought_products')]
    public function boughtProducts(): Response
    {
        $user = $this->getUser();
        if(!$user){
            return $this->redirectToRoute('app_home');
        }
        $products = $this->productService->getBoughtProduct($user);
     
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

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
