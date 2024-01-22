<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Nutrition;
use App\Entity\Promotion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupération des catégories et sous-catégories
        $categoryRepository = $manager->getRepository(Category::class);
        $subCategoryRepository = $manager->getRepository(SubCategory::class);
        
        $categories = $categoryRepository->findAll();
        $subCategories = $subCategoryRepository->findAll();

        // Création des promotions
        for ($i = 0; $i < 200; $i++) {
            $promotion = new Promotion();
            $promotion->setRising($faker->randomElement(['5','10','15','20','25','30','40']));
            $manager->persist($promotion);
            $promotions[] = $promotion;
        }

        // Création des produits
        for ($i = 0; $i < 1800; $i++) {
            $product = new Product();
            $product->setName($faker->words(3, true)); 
            $product->setDescription($faker->sentence);
            $product->setPrice($faker->randomFloat(2, 1, 100));
            $product->setImage('images/chocolate.jpg'); 
            $product->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')));



            // Association d'une catégorie, d'une sous-catégorie et d'une promotion aléatoires
            $product->addCategory($faker->randomElement($categories));
            $product->addSubCategory($faker->randomElement($subCategories));
            // Attribution 30% de chance d'avoir une promotion
            if ($faker->boolean($chanceOfGettingTrue = 30)) { 
                $product->setPromotion($faker->randomElement($promotions));
            }
            
            // Création des informations nutritionnelles
            $nutrition = new Nutrition();
            $nutrition->setEnergy($faker->numberBetween(50, 500)); // kcal pour 100g
            $nutrition->setFibers($faker->randomFloat(2, 0, 15)); // g pour 100g
            $nutrition->setLipids($faker->randomFloat(2, 0, 35)); // g pour 100g
            $nutrition->setProteins($faker->randomFloat(2, 0, 50)); // g pour 100g
            $nutrition->setSalt($faker->randomFloat(2, 0, 5)); // g pour 100g
            $nutrition->setSaturatedFattyAcid($faker->randomFloat(2, 0, 20)); // g pour 100g
            $nutrition->setCarbohydrates($faker->randomFloat(2, 0, 70)); // g pour 100g
            $nutrition->setSugar($faker->randomFloat(2, 0, 50)); // g pour 100g
            $manager->persist($nutrition);
            $nutritions[] = $nutrition;
            
            $product->setNutrition($nutrition);

            $manager->persist($product);
        }

        $manager->flush();
    }
}

