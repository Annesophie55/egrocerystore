<?php

namespace App\Services;

use App\Entity\Product;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenFoodFactsService{

  private $client;
  private $productsData;
  private $entityManagerInterface;


  public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManagerInterface)
  {
    $this->client = $client;
    $this->entityManagerInterface = $entityManagerInterface;
  }


  public function fetchProductBarcodes(): array {
    $response = $this->client->request('GET', "https://world.openfoodfacts.org/cgi/search.pl?action=process&sort_by=unique_scans_n&page_size=20&json=true");
    $data = $response->toArray();
    $barcodes = array_column($data['products'], 'code');
    return $barcodes;
  }

  public function fetchProductData(string $barcode):array{
    $response = $this->client->request('GET', "https://world.openfoodfacts.org/api/v0/product/{$barcode}.json");
    return $response->toArray();
  }

  public function fetchCategories(): array {
    $response = $this->client->request('GET', "https://world.openfoodfacts.org/categories.json");
    $data = $response->toArray();
    
    return $data['tags'] ?? [];
  }



  public function setProductDataTable($barcode):void{
    $dataFromApi = $this->fetchProductData($barcode);
    dd($dataFromApi);

    $product = new Product();
    $product->setName($dataFromApi['product_name'] ?? 'Unknown');
    $product->setDescription($dataFromApi['description'] ?? '');
    $product->setPrice($dataFromApi['price'] ?? 0);
    $product->setImage($dataFromApi['image_url'] ?? '');

    $this->entityManagerInterface->persist($product);
    $this->entityManagerInterface->flush();
  }



public function fetchProductsByCategory(string $category, int $numberOfProducts = 10): array {
  $response = $this->client->request('GET', "https://world.openfoodfacts.org/category/$category/$numberOfProducts.json");
  return $response->toArray();
}

public function importProductsFromCategories(array $categories): void {
  foreach ($categories as $category) {
      try {
          $productsData = $this->fetchProductsByCategory($category);
          foreach ($productsData['products'] as $productData) {
              $barcode = $productData['code'] ?? null;
              if ($barcode) {
                  $this->setProductDataTable($barcode);
              }
          }
      } catch (\Exception $exception) {

      }
  }
}

}