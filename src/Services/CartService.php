<?php


namespace App\Services;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService {
    private $session;
    private $productRepository;

    public function __construct(RequestStack $requestStack, ProductRepository $productRepository) {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
    }

    public function getCart() {
        return $this->session->get('cart', []);
    }

    public function getIssues() {
        return $this->session->get('stockIssues', []);
    }

    public function addToCart($productId, $quantity = 1) {
        $cart = $this->getCart();
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \Exception("Produit introuvable.");
        }

        if (!isset($cart[$productId])) {
            $cart[$productId] = [
                'quantity' => $quantity,
                'unitPrice' => $product->getPrice(),
                'total' => $product->getPrice() * $quantity
            ];
        } else {
            $cart[$productId]['quantity'] += $quantity;
            $cart[$productId]['total'] = $cart[$productId]['unitPrice'] * $cart[$productId]['quantity'];
        }

        $this->session->set('cart', $cart);
    }

    public function removeFromCart($productId, $quantity = 1) {
        $cart = $this->getCart();
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] -= $quantity;
            if ($cart[$productId]['quantity'] <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['total'] = $cart[$productId]['unitPrice'] * $cart[$productId]['quantity'];
            }
        }

        $this->session->set('cart', $cart);
    }

    public function getCartTotal() {
        $cart = $this->getCart();
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['total'];
        }
        return $total;
    }

    public function getCartQuantity() {
        $cart = $this->getCart();
        $quantity = 0;
        foreach ($cart as $item) {
            $quantity += $item['quantity'];
        }
        return $quantity;
    }
    

    public function emptyCart() {
        $this->session->set('cart', []);
    }

    public function updateCart(Request $request, $productId) {
        $action = $request->request->get('action');
    
        switch ($action) {
            case 'increase':
                $this->addToCart($productId);
                break;
            case 'decrease':
                $this->removeFromCart($productId);
                break;
        }
    }
}
