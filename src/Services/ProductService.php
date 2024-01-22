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

    public function getProducts(){

        $products = $this->productRepository->findAllProductsWithDetails();

        $productsWithnutriScore = [];
        
        foreach ($products as $product) {
        $nutriScore = $this->calculatenutriScore($product);
    
        $productsWithnutriScore[] = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];}
        return $productsWithnutriScore;
  }

  public function getRecentlyProductWithFavorites($user = null) {

    $products = $this->productRepository->findByRecentlyDate(16);

    $favoriteProductIds = [];

    // Si l'utilisateur est connecté, récupérez ses produits favoris
    if ($user) {
        $favoriteProductIds[] = $user->getFavorite();
    }

    $productsWithnutriScoreAndFavorites = [];

    foreach ($products as $product) {
        $nutriScore = $this->calculateNutriScore($product);
        $isFavorite = in_array($product->getId(), $favoriteProductIds);

        $productsWithnutriScoreAndFavorites[] = [
            'product' => $product,
            'nutriScore' => $nutriScore,
            'isFavorite' => $isFavorite,
        ];
    }

    // Tri des produits : favoris en premier, puis par date
    usort($productsWithnutriScoreAndFavorites, function ($a, $b) {
        if ($a['isFavorite'] === $b['isFavorite']) {
            return $b['product']->getCreatedAt() <=> $a['product']->getCreatedAt();
        }
        return $b['isFavorite'] <=> $a['isFavorite'];
    });

    return $productsWithnutriScoreAndFavorites;
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

        return $productDétails = [
            'product' => $product,
            'nutriScore' => $nutriScore,
        ];
    }

    public function getFilteredProducts($sort, $category, $priceMax, $priceRange, $nutriScore) {
        // Commencer avec tous les produits
        $queryBuilder = $this->productRepository->createQueryBuilder('p')
            ->select('p', 'nutrition', 'promotion')
            ->leftJoin('p.nutrition', 'nutrition')
            ->leftJoin('p.promotion', 'promotion');

        // Appliquer les filtres un par un
        if ($sort) {
            $sortOrder = $sort === 'ascprice' ? 'ASC' : 'DESC';
            $queryBuilder->orderBy('p.price', $sortOrder);
        }

        if ($category) {
            $queryBuilder->andWhere('c.id = :category')
                        ->setParameter('category', $category);
        }

        if ($priceMax) {
            $queryBuilder->andWhere('p.price <= :maxPrice')
                        ->setParameter('maxPrice', $priceMax);
        }

        if ($priceRange) {
            // Assurez-vous que priceRange contient les clés 'min' et 'max'
            $queryBuilder->andWhere('p.price >= :minPrice AND p.price <= :maxPrice')
                        ->setParameter('minPrice', $priceRange['min'])
                        ->setParameter('maxPrice', $priceRange['max']);
        }

        // Obtenez les résultats préliminaires sans le filtre NutriScore
        $products = $queryBuilder->getQuery()->getResult();

        // Si NutriScore est fourni, appliquez le filtre en PHP
        if ($nutriScore) {
            $products = array_filter($products, function($product) use ($nutriScore) {
                $calculatedNutriScore = $this->calculateNutriScore($product);
                return $calculatedNutriScore <= $nutriScore;
            });
        }

        return $products;
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

    public function getProductsBySubCategory($subCategory_id){

        $products = $this->productRepository->findAllProductsWithDetailsBySubCategory($subCategory_id);

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