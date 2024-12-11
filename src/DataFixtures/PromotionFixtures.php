<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Product;
use App\Entity\Promotion;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

;

class PromotionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $productRepository =$manager->getRepository(Product::class);
        $products = $productRepository->findAll();

        if(count($products) === 0) {
            throw new \Exception('Aucun produit trouvé en base de données.');
        }

        for($i = 0; $i < 20; $i++) {
            $promotion = new Promotion;

            $startDate = $faker->dateTimeBetween('-1 month', '+1 month');
            $endDate = $faker->dateTimeBetween($startDate, '+1 month');
            $type = $faker->randomElement(['percentage', 'fixed']);

            if($type === 'percentage') {
                $rising = $faker->randomElement([5, 10, 20, 25]);
            }

            if($type === 'fixed') {
                $rising = $faker->randomElement([1, 2, 0.5]);
            }

            $promotion->setName($faker->word(3, true))
                ->setDescription($faker->sentence(10))
                ->setDiscountType($type)
                ->setRising($rising)
                ->setStartDate(\DateTimeImmutable::createFromMutable($startDate))
                ->setEndDate(\DateTimeImmutable::createFromMutable($endDate))
                ->setIsActive(false)
                ->setIsManualOverride(false);

            $promotion->updateIsActive();

            $numberOfProducts = $faker->numberBetween(10, 25); // Associer entre 1 et 5 produits
            $randomProducts = $faker->randomElements($products, $numberOfProducts);

            foreach ($randomProducts as $product) {
                $product->setPromotion($promotion);
                $manager->persist($product);
            }

            $manager->persist($promotion);
        }
        $manager->flush();
    }
}
