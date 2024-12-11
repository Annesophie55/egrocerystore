<?php

namespace App\Services;

use App\Repository\PromotionRepository;

Class PromotionService{

  private $promotionRepository;

  public function __construct(PromotionRepository $promotionRepository){

    $this->promotionRepository = $promotionRepository;

  }

  public function getPromotionsForCarousel(){

    return $this->promotionRepository->findActivateForCarousel();

  }

  public function getPromotionsForProducts(){

    return $this->promotionRepository->findActivateForProducts();
    
  }
}