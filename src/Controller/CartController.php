<?php

namespace App\Controller;

use App\Entity\Order;
use DateTimeImmutable;
use App\Entity\OrderItem;
use App\Services\CartService;
use Doctrine\ORM\EntityManager;
use App\Services\CategoryService;
use App\Repository\CartRepository;
use App\Repository\OrderItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/cart')]
class CartController extends AbstractController
{
    private $cartService;
    private $entityManager;
    private $productRepository;

    public function __construct(CartService $cartService, EntityManagerInterface $entityManager, ProductRepository $productRepository){
      $this->cartService = $cartService;
      $this->entityManager = $entityManager;
      $this->productRepository = $productRepository;
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
            'total' => $total
        ]);
    }

    #[Route('/validation', name: 'app_cart_validation', methods: 'POST')]
    public function cartValidation(Request $request) {
        
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }    
        // Récupération des informations du panier en session.
        $cart = $this->cartService->getCart();
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }
    
        // Récupération du choix de retrait de la commande.
        $deliveryChoice = $request->request->get('deliveryOption');
    
        // Création la commande.
        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new DateTimeImmutable());
        $order->setWithdrawalChoice($deliveryChoice);
        $order->setStatus('Validé');
        $order->setTotalPrice($this->cartService->getCartTotal());
        $order->setQuantity($this->cartService->getCartQuantity());
        
        // Création des lignes de commande pour chaque produit dans le panier.
        foreach ($cart as $productId => $details) {
            $product = $this->productRepository->find($productId);
            if ($product) {
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($details['quantity']);
                $orderItem->setTotalPrice($details['total']);
                $orderItem->setCreatedAt(new DateTimeImmutable());
                $orderItem->setStatus('En préparation');
                $orderItem->setOrders($order);
                $this->entityManager->persist($orderItem);
            }
        }
       
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    
        // Vide le panier en session après la validation de la commande.
        $this->cartService->emptyCart();
    
        $this->addFlash('success', 'Votre commande a été validée avec succès !');
        return $this->redirectToRoute('app_home_page');
    }
    
}
