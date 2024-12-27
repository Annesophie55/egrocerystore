<?php
namespace App\Services;

use Doctrine\ORM\QueryBuilder;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    private $paginator;

    public function __construct(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }

    public function paginate(QueryBuilder $queryBuilder, Request $request, int $limit = 10): array
    {
        // Récupérer la page actuelle depuis la requête
        $page = max(1, $request->query->getInt('page', 1));
    
        // Calcul du total des produits
        $totalProducts = count($queryBuilder->getQuery()->getResult());
    
        // Calcul du total des pages
        $totalPages = ceil($totalProducts / $limit);
    
        // Pagination des résultats
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    
        return [
            'pagination' => $pagination,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];
    }    
}
