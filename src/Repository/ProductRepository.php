<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

  public function findBySmallPrice($price, $limit):array{
      return $this->createQueryBuilder('p')
      ->select('p','nutrition', 'promotion')
      ->leftJoin('p.nutrition', 'nutrition')
      ->leftJoin('p.promotion', 'promotion')
      ->andWhere('promotion.rising <= :price')
      ->setParameter('price', $price)
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  public function findByPromotion($limit):array{
    return $this->createQueryBuilder('p')
    ->select('p','nutrition', 'promotion')
    ->leftJoin('p.nutrition', 'nutrition')
    ->leftJoin('p.promotion', 'promotion')
    ->andWhere('promotion.rising IS NOT null')
    ->setMaxResults($limit)
    ->getQuery()
    ->getResult();
  }

    public function findByRecentlyDate($limit):array{
        return $this->createQueryBuilder('p')
                    ->select('p','nutrition', 'promotion')
                    ->leftJoin('p.nutrition', 'nutrition')
                    ->leftJoin('p.promotion', 'promotion')
                    ->orderBy('p.createdAt', 'DESC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

    }


    public function findOneProductsWithDetails($id){
      return $this->createQueryBuilder('p')
                  ->select('p','nutrition', 'promotion')
                  ->leftJoin('p.nutrition', 'nutrition')
                  ->leftJoin('p.promotion', 'promotion')
                  ->andWhere('p.id = :id')
                  ->setParameter('id' , $id)
                  ->getQuery()
                  ->getOneOrNullResult();
  }

  public function findAllProductsWithDetailsByCategory($category_id){
    return $this->createQueryBuilder('p')
        ->select('p', 'nutrition', 'promotion', 'c')
        ->leftJoin('p.nutrition', 'nutrition')
        ->leftJoin('p.promotion', 'promotion')
        ->innerJoin('p.categories', 'c')
        ->where('c.id = :category_id')
        ->setParameter('category_id', $category_id)
        ->getQuery()
        ->getResult();            
  }  


  public function searchProducts($query)
  {
    return $this->createQueryBuilder('p')
                ->where('p.name LIKE :query')
                ->setParameter('query', '%'.$query.'%')
                ->getQuery()
                ->getResult();  
  }


//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
