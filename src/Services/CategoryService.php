<?php

namespace App\Services;


use App\Repository\CategoryRepository;

class CategoryService{

  private $categoryRepository;

  public function __construct(CategoryRepository $categoryRepository)
  {
      $this->categoryRepository = $categoryRepository;
  }

  public function getTopLevelCategoriesWithChildren()
    {
        $topLevelCategories = $this->categoryRepository->findBy(['parent' => null]);

        $categoriesWithChildren = [];
        foreach ($topLevelCategories as $category) {
            $children = $this->categoryRepository->findBy(['parent' => $category]);
            $categoriesWithChildren[] = [
                'category' => $category,
                'children' => $children
            ];
        }

        return $categoriesWithChildren;
    }
}