<?php

namespace App\TwigFilter;

use App\Entity\Product;
use App\Services\ProductService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProductFilter extends AbstractExtension
{
  private $productService;
  
  public function __construct(ProductService $productService){
    $this->productService = $productService;
  }

    public function getFilters():array
    {
        return [
            new TwigFilter('nutriscore', [$this, 'productNutriscore']),
        ];
    }

    public function productNutriscore(Product $product)
    {
        return $this->productService->calculatenutriScore($product);
    }
}