<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Services\ProductService;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Services\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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

    #[Route('/list', name: 'app_product_list')]
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
    public function productByCategory($category_id, CategoryRepository $categoryRepository)
    {
        $category = $categoryRepository->find(['id'=>$category_id]);

        $products = $this->productService->getProductsBycategory($category_id);


        return $this->render('product/index.html.twig', [
            'products' => $products,
            'category' => $category
        ]);
    }

    #[Route('/promotions', name:'app_product_promotion')]
    public function showPromotions()
    {
        $products = $this->productService->getByPromotion(50);

        return $this->render('product/index.html.twig',[
            'products' => $products,
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

    #[Route("/api/search/user/", name:"search_user")]
    public function searchUser(Request $request, ProductRepository $productRepository): Response {

        $query = $request->query->get('query');
        if ($query) {
            $products = $productRepository->searchProducts($query);
        } else {
            $products = $productRepository->findAll();
        }

        return $this->render('product/index.html.twig',[
            'products' => $products,
        ]);
    }

    #[Route('/add', name: 'app_product_add')]
    public function addProduct(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
               /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
    
                try {
                    $newFilename = $this->fileUploader->upload($imageFile, $this->getParameter('images_directory'));
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('alert', 'Un problème est survenu lors du téléchargement de l\'image. Veuillez réessayer.');
                }
    
                $product->setImage($newFilename);
            }
    
            $entityManager->persist($product);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_product');
        }
    
        return $this->render('product/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_product_edit')]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
         $imageFile = $form->get('imageFile')->getData();
         if ($imageFile) {
 
             try {
                $newFilename = $this->fileUploader->upload($imageFile, $this->getParameter('images_directory'));
                $product->setImage($newFilename);
             } catch (FileException $e) {
                 $this->addFlash('alert', 'Un problème est survenu lors du téléchargement de l\'image. Veuillez réessayer.');
             }
 
             $product->setImage($newFilename);
         }
    
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
