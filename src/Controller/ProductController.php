<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Services\FileUploader;
use App\Services\ProductService;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/product')]
class ProductController extends AbstractController
{
    private $productService;
    private $entityManager;
    private $fileUploader;

    public function __construct(EntityManagerInterface $entityManager,ProductService $productService, FileUploader $fileUploader)
    {
        $this->productService = $productService;
        $this->entityManager = $entityManager;
        $this->fileUploader = $fileUploader;
    }

    #[Route('/', name: 'app_product')]
    public function index(): Response
    {
        $products = $this->productService->getProducts();
     
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/admin/list', name: 'app_product_list')]
    public function list(Request $request, ProductRepository $productRepository, PaginatorInterface $paginator): Response
    {

        $queryBuilder = $productRepository->createQueryBuilder('p');

        $page = max(1, $request->query->getInt('page', 1)); 
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            10 
        );
    
        return $this->render('product/list.html.twig', [
            'pagination' => $pagination,
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
    public function productByCategory($category_id, CategoryRepository $categoryRepository, ProductService $productService)
    {
        $category = $categoryRepository->find(['id'=>$category_id]);

        $products = $this->productService->getProductsBycategory($category_id);

        $productInPromotionForCarousel = $productService->getByPromotion(6);


        return $this->render('product/index.html.twig', [
            'products' => $products,
            'category' => $category,
            'productInPromotionForCarousel' => $productInPromotionForCarousel
        ]);
    }

    #[Route('/promotions', name:'app_product_promotion')]
    public function showPromotions(ProductService $productService)
    {
        $products = $this->productService->getByPromotion(50);

        $productInPromotionForCarousel = $productService->getByPromotion(6);

        return $this->render('product/index.html.twig',[
            'products' => $products,
            'productInPromotionForCarousel' => $productInPromotionForCarousel
        ]);
    }

    #[Route('/favorite', name:'app_product_favorite')]
    public function showFavoris()
    {
        $user = $this->getUser();
        if(!$user){
            return $this->redirectToRoute('app_home');
        }
        $products = $this->productService->getFavoritesProducts($user);

        return $this->render('product/index.html.twig',[
            'products' => $products,
        ]);
    }

    #[Route("/api/search/product/", name:"search_product")]
    public function searchUser(
        Request $request, 
        ProductRepository $productRepository, 
        ProductService $productService, 
        PaginatorInterface $paginator
    ): Response {
    
        $query = $request->query->get('query');
        $products = $query 
            ? $productRepository->searchProducts($query) 
            : $productRepository->findAll();
    
        $queryBuilder = $productRepository->createQueryBuilder('p');
        $page = max(1, $request->query->getInt('page', 1)); 
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            10
        );

        $productInPromotionForCarousel = $productService->getByPromotion(6);
    
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'productInPromotionForCarousel' => $productInPromotionForCarousel,
            'pagination' => $pagination,
        ]);
    }

    #[Route("/admin/api/search/product/", name:"search_product_admin")]
    public function searchProductAdmin(
        Request $request, 
        ProductRepository $productRepository, 
        PaginatorInterface $paginator
    ): Response {
    
        $query = $request->query->get('query');

        $queryBuilder = $productRepository->createQueryBuilder('p');

        if ($query) {
            $queryBuilder
                ->where('p.name LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }
        
        $page = max(1, $request->query->getInt('page', 1));

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            10
        );
    
        return $this->render('product/list.html.twig', [
                'pagination' => $pagination,
            ]);
    
    }
    
    

    #[Route('/add', name: 'app_product_add')]
    public function addProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
    
            try {
                // Appel au service ProductService pour gérer l'image
                $this->productService->handleImageUpload($imageFile, $product, $this->getParameter('images_directory'));
            } catch (\Exception $e) {
                $this->addFlash('alert', $e->getMessage());
            }
    
            $product->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($product);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_product_list');
        }
    
        return $this->render('product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{id}', name: 'app_product_edit')]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
    
            try {
                // Appel au service ProductService pour gérer l'image
                $this->productService->handleImageUpload($imageFile, $product, $this->getParameter('images_directory'));
            } catch (\Exception $e) {
                $this->addFlash('alert', $e->getMessage());
            }
    
            $product->setUpdatedAt(new DateTimeImmutable());
            $entityManager->flush();
    
            return $this->redirectToRoute('app_product_list');
        }
    
        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }    

    #[Route('/delete/{id}', name: 'app_product_delete')]
    public function deleteProduct(Product $product): Response
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_product');
    }
}
