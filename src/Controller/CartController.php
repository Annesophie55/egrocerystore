<?php

namespace App\Controller;

use App\Entity\Order;
use DateTimeImmutable;
use App\Entity\OrderItem;
use App\Services\CartService;
use Doctrine\ORM\EntityManager;
use App\Services\CategoryService;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/cart')]
class CartController extends AbstractController
{
    private $cartService;
    private $entityManager;
    private $productRepository;
    private $session;


    public function __construct(CartService $cartService, EntityManagerInterface $entityManager, ProductRepository $productRepository, RequestStack $requestStack, ){
      $this->cartService = $cartService;
      $this->entityManager = $entityManager;
      $this->productRepository = $productRepository;
      $this->session = $requestStack->getSession();
    }

    #[Route('/add/{productId}', name: 'app_add_to_cart')]
    public function add($productId) {
        try {
            $this->cartService->addToCart($productId);
            $this->addFlash('success', 'Produit ajouté au panier.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_page');
    }

    #[Route('/', name: 'app_cart')]
    public function index() {

        //Je récupère le panier et son total 
        $cart = $this->cartService->getCart();

        $total = $this->cartService->getCartTotal();

        $stockIssues = $this->cartService->getIssues();

        // Je convertis les IDs de produits dans le panier en entités complètes
        $products = [];
        foreach ($cart as $productId => $details) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'quantity' => $details['quantity'],
                    'total' => $details['total']
                ];
            }
        }

        return $this->render('cart/index.html.twig', [
            'products' => $products,
            'total' => $total,
            'stockIssues' => $stockIssues,
        ]);
    }

    #[Route('/validation', name: 'app_cart_validation', methods: ['POST'])]
    public function cartValidation(Request $request) {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        $cart = $this->cartService->getCart();
    
        if (empty($cart)) {
            return $this->render('cart/index.html.twig', ['error' => 'Votre panier est vide.']);
        }
    
        $deliveryChoice = $request->request->get('deliveryOption');
    
        $order = new Order();
        $order->setUser($user)
              ->setCreatedAt(new \DateTimeImmutable())
              ->setWithdrawalChoice($deliveryChoice)
              ->setStatus('Validé')
              ->setTotalPrice($this->cartService->getCartTotal())
              ->setQuantity($this->cartService->getCartQuantity());
    
        // Vérifie les problèmes de stock
        $stockIssues = [];
        foreach ($cart as $productId => $details) {
            $product = $this->productRepository->find($productId);
            if ($product && $product->getQuantity() < $details['quantity']) {
                $stockIssues[] = [
                    'productName' => $product->getName(),
                    'available' => $product->getQuantity(),
                    'requested' => $details['quantity'],
                ];
            }
        }
    
        if (!empty($stockIssues)) {
            $this->session->set('stockIssues', $stockIssues);
            return $this->redirectToRoute('app_cart');
        }
    
        // Crée les éléments de la commande si aucun problème de stock n'est détecté
        foreach ($cart as $productId => $details) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($product)
                          ->setQuantity($details['quantity'])
                          ->setTotalPrice($details['total'])
                          ->setCreatedAt(new \DateTimeImmutable())
                          ->setStatus('En préparation')
                          ->setOrders($order); 
                $this->entityManager->persist($orderItem);
    
                // Mise à jour de la quantité du produit
                $product->setQuantity($product->getQuantity() - $details['quantity']);
                $this->entityManager->persist($product);
            }
        }
    
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $this->cartService->emptyCart();
    
        return $this->redirectToRoute('app_order_index');
    }
    


    #[Route('/update/{productId}', name: 'app_update_cart')]
    public function updateCartInCart(Request $request, $productId) {
        $action = $request->request->get('action');
    
        $this->cartService->updateCart($request, $productId);
    
        return $this->redirectToRoute('app_cart');
    }
    
}
