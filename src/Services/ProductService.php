<?php

namespace App\Services;

use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductService{
  private $productRepository;
  private $requestStack;
  private $promotionRepository;

  public function __construct(ProductRepository $productRepository, RequestStack $requestStack, PromotionRepository $promotionRepository)
  {
    $this->productRepository = $productRepository;
    $this->requestStack = $requestStack;
  }

    public function getProducts($page = 1, $limit = 10){

        $products = $this->productRepository->findAll();

        return $products;
  }


    public function calculatenutriScore($product):string{

        // recupération des valeurs nutritives
        $nutrition = $product->getNutrition();

        if (!$nutrition) {
            return null; // Ou 'Non disponible', ou toute autre valeur par défaut
        }

        // verification de la récupération
        if($nutrition){
        $energy = $nutrition->getEnergy();

        // conversion de l'ernergie en kilocalories
        $kilocalories = $energy *  0.239;

        // récupération des valeurs nutritionnelles des différents composant du produit
        $percentageLipids = ($nutrition->getLipids() * $kilocalories) / 100;
        $percentageSaturatedFattyAcid = ($nutrition->getSaturatedFattyAcid() * $kilocalories) / 100;
        $percentageSugar = ($nutrition->getSugar() * $kilocalories) / 100;
        $percentageSalt = ($nutrition->getSalt() * $kilocalories) / 100;
        $percentageProteins = ($nutrition->getProteins() * $kilocalories) / 100;
        $percentageFibers = ($nutrition->getFibers() * $kilocalories) / 100;
        $percentageCarbohydrates = ($nutrition->getCarbohydrates() * $kilocalories) / 100;

        // calcul des points par composant nutritionnel
        $pointsLipids = 0;

        if ($percentageLipids <= 3) {
            $pointsLipids = 0;
        } elseif ($percentageLipids <= 10) {
            $pointsLipids = 1;
        } elseif ($percentageLipids <= 20) {
            $pointsLipids = 2;
        } elseif ($percentageLipids <= 30) {
            $pointsLipids = 3;
        } else {
            $pointsLipids = 4;
        }

        $pointsSaturatedFattyAcid = 0;

        if ($percentageSaturatedFattyAcid <= 1) {
            $pointsSaturatedFattyAcid = 0;
        } elseif ($percentageSaturatedFattyAcid <= 2) {
            $pointsSaturatedFattyAcid = 1;
        } else {
            $pointsSaturatedFattyAcid = 2;
        }

        $pointsSugar = 0;

        if ($percentageSugar <= 5) {
            $pointsSugar = 0;
        } elseif ($percentageSugar <= 10) {
            $pointsSugar = 1;
        } elseif ($percentageSugar <= 15) {
            $pointsSugar = 2;
        } elseif ($percentageSugar <= 22.5) {
            $pointsSugar = 3;
        } else {
            $pointsSugar = 4;
        }

        $pointsSalt = 0;

        if ($percentageSalt <= 0.4) {
            $pointsSalt = 0;
        } elseif ($percentageSalt <= 1.6) {
            $pointsSalt = 1;
        } else {
            $pointsSalt = 2;
        }

        $pointsProteins = 0;

        if ($percentageProteins <= 1.6) {
            $pointsProteins = 0;
        } elseif ($percentageProteins <= 3.2) {
            $pointsProteins = 1;
        } else {
            $pointsProteins = 2;
        }

        $pointsFibers = 0;

        if ($percentageFibers >= 3.0) {
            $pointsFibers = 0;
        } elseif ($percentageFibers >= 1.5) {
            $pointsFibers = 1;
        } else {
            $pointsFibers = 2;
        }

        $pointsCarbohydrates = 0;

        if ($percentageCarbohydrates <= 8.8) {
            $pointsCarbohydrates = 0;
        } elseif ($percentageCarbohydrates <= 17.5) {
            $pointsCarbohydrates = 1;
        } else {
            $pointsCarbohydrates = 2;
        }

        // calcul du nutriScore en point
        $nutriScorePts = $pointsLipids + $pointsSaturatedFattyAcid + $pointsSugar + $pointsSalt + $pointsProteins + $pointsFibers + $pointsCarbohydrates;

        // attribution de la lettre correspondante aux points
        if($nutriScorePts <= 2){
            $nutriScore = 'A';
        }elseif($nutriScorePts <= 10){
            $nutriScore = 'B';
        }elseif($nutriScorePts <= 18){
            $nutriScore = 'C';
        }elseif($nutriScorePts <= 27){
            $nutriScore = 'D';
        }else{
            $nutriScore = 'E';
        }
        }
        else{
        // gérer les erreurs avec addflash message
        }

        return $nutriScore;
    }

    public function getRecentlyProduct(){

        $products = $this->productRepository->findByRecentlyDate(16);

        $productsWithnutriScore = [];
        
        foreach ($products as $product) {
        $nutriScore = $this->calculatenutriScore($product);
    
        $productsWithnutriScore[] = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];
    }
    
        return $productsWithnutriScore;
    }

    public function getOneProduct($id){

        $product = $this->productRepository->findOneProductsWithDetails($id);

        $nutriScore = $this->calculatenutriScore($product);

        $productDétails = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];

        return $productDétails;
    }

    public function getProductsBycategory($category){

        $products = $this->productRepository->findAllProductsWithDetailsByCategory($category);

        $productsWithnutriScore = [];
        
        foreach ($products as $product) {
        $nutriScore = $this->calculatenutriScore($product);
    
        $productsWithnutriScore[] = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];}

        return $productsWithnutriScore;

    }

    public function getFavoritesProducts($user){

        // Récupération des favoris de l'utilisateur
        $favorites = $user->getFavorite()->toArray();
        
                    // Transformation des favoris en tableau avec les données nécessaires pour l'affichage
                    $favoritesData = array_map(function ($favoriteProduct) {
                        // Assurez-vous que $favoriteProduct est un objet Product
                        return [
                            'product' => $favoriteProduct,
                            'promotion' => $favoriteProduct->getPromotion(),
                            'nutriScore' => $this->calculateNutriScore($favoriteProduct),
                        ];
                    }, $favorites);
            return $favoritesData;
        }

    public function getBoughtProduct($user){
         // Récupération des commandes et des produits achetés
         $orders = $user->getOrder(); 
         $ordersArray = $orders->toArray();
     
         $buyProductsData = [];

         if ($ordersArray) {

             foreach ($ordersArray as $order) {

                 $orderItems = $order->getOrderItems();

                 foreach ($orderItems as $orderItem) {

                     $product = $orderItem->getProduct();

                     if ($product) {
                         $buyProductsData[] = [
                             'product' => $product,
                             'promotion' => $product->getPromotion(),
                             'nutriScore' => $this->calculateNutriScore($product),
                         ];
                     }
                 }
             }
         }
        return $buyProductsData;
    }

    public function getSmallPrice(){
    $products = $this->productRepository->findBySmallPrice(12, 12);
    $productsWithnutriScore = [];
        
    foreach ($products as $product) {
    $nutriScore = $this->calculatenutriScore($product);

    $productsWithnutriScore[] = [
        'product' => $product,
        'nutriScore' => $nutriScore,
    ];}

    return $productsWithnutriScore;
    }

    public function getByPromotion(){
        $products = $this->productRepository->findByPromotion(4);
        $productsWithnutriScore = [];
        
        foreach ($products as $product) {
        $nutriScore = $this->calculatenutriScore($product);
    
        $productsWithnutriScore[] = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];}
    
        return $productsWithnutriScore;
    }
}