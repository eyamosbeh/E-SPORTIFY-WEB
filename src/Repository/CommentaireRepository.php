<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Find comments with search and filter options.
     *
     * @param string|null $search Search term for content or author
     * @param bool|null $signaled Filter by signaled status
     * @return Commentaire[] Returns an array of Commentaire objects
     */
    public function findByFilters(?string $search = null, ?bool $signaled = null): array
    {
        $qb = $this->createQueryBuilder('c');

        // Search across content and author
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(c.contenu) LIKE LOWER(:search)',
                    'LOWER(c.auteur) LIKE LOWER(:search)'
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // Filter by signaled status
        if ($signaled !== null) {
            $qb->andWhere('c.signaled = :signaled')
                ->setParameter('signaled', $signaled);
        }

        $qb->orderBy('c.dateCreation', 'DESC');

        return $qb->getQuery()->getResult();
    }
}