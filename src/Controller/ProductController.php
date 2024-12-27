<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Services\FileUploader;
use App\Services\ProductService;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Services\PaginationService;
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

        $pageTitle = 'Tous les produits';
     
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'pageTitle' => $pageTitle
        ]);
    }

    #[Route('/admin/list', name: 'app_product_list')]
    public function list(PaginationService $paginationService, ProductRepository $productRepository, Request $request): Response
    {
        $queryBuilder = $productRepository->createQueryBuilder('p'); // Requête pour récupérer tous les produits
        $pagination = $paginationService->paginate($queryBuilder, $request);
    
        return $this->render('product/list.html.twig', [
            'pagination' => $pagination,
            'pageTitle' => 'Liste des produits',
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

        $pageTitle = 'Vos dernoers achats';
     
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'pageTitle' => $pageTitle
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

    #[Route('/category/{category_id}', name: 'app_product_category')]
public function productsByCategory(
    $category_id,
    CategoryRepository $categoryRepository,
    ProductRepository $productRepository,
    PaginationService $paginationService,
    Request $request
): Response {
    $category = $categoryRepository->find($category_id);
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée.');
    }

    $queryBuilder = $productRepository->createQueryBuilder('p')
        ->leftJoin('p.categories', 'c')
        ->addSelect('c')
        ->where('c.id = :category_id')
        ->setParameter('category_id', $category_id);

    $paginationData = $paginationService->paginate($queryBuilder, $request);

    return $this->render('product/index.html.twig', [
        'pagination' => $paginationData['pagination'],
        'pageTitle' => "Produits dans la catégorie {$category->getName()}",
        'totalPages' => $paginationData['totalPages'],
        'currentPage' => $paginationData['currentPage'],
         'routeName' => 'app_product_category',
         'queryParameters' => ['category_id' => $category_id],
    ]);
}

    

    #[Route('/promotions', name: 'app_product_promotion')]
    public function promotions(
        ProductRepository $productRepository,
        PaginationService $paginationService,
        Request $request
    ): Response {
        // Récupérer le QueryBuilder pour les produits en promotion
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.promotion', 'promotion')
            ->addSelect('promotion')
            ->where('promotion.rising IS NOT NULL');
    
        // Appeler le service de pagination
        $paginationData = $paginationService->paginate($queryBuilder, $request);
    
        return $this->render('product/index.html.twig', [
            'pagination' => $paginationData['pagination'],
            'pageTitle' => "Produits en Promotion",
            'totalPages' => $paginationData['totalPages'],
            'currentPage' => $paginationData['currentPage'],
            'routeName' => 'app_product_promotion'
        ]);
    }
    
    

    #[Route('/favorite', name: 'app_product_favorite')]
    public function showFavorites(
        ProductService $productService,
        PaginationService $paginationService,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
    
        $favorites = $productService->manageFavorites($user);
    
        return $this->render('product/index.html.twig', [
            'products' => $favorites,
            'pageTitle' => "Vos produits favoris",
        ]);
    }
    

    #[Route("/api/search/product/", name:"search_product")]
    public function searchUser(
        Request $request, 
        ProductRepository $productRepository, 
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

        $pageTitle = "Résultat de la recherche";
    
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'pagination' => $pagination,
            'pageTitle' => $pageTitle
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
