<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Find posts with search, filter, and sort options.
     *
     * @param string|null $search Search term for title, description, or category
     * @param string|null $category Filter by category
     * @param bool|null $status Filter by enable status
     * @param bool|null $signaled Filter by signaled status
     * @param string|null $sortBy Field to sort by (likeCount, dislikeCount, updatedAt)
     * @param string|null $sortOrder Sort order (ASC, DESC)
     * @return Post[] Returns an array of Post objects
     */
    public function findByFilters(
        ?string $search = null,
        ?string $category = null,
        ?bool $status = null,
        ?bool $signaled = null,
        ?string $sortBy = null,
        ?string $sortOrder = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        // Search across title, description, and category
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(p.titre) LIKE LOWER(:search)',
                    'LOWER(p.description) LIKE LOWER(:search)',
                    'LOWER(p.categorie) LIKE LOWER(:search)'
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // Filter by category
        if ($category) {
            $qb->andWhere('p.categorie = :category')
                ->setParameter('category', $category);
        }

        // Filter by status (enable)
        if ($status !== null) {
            $qb->andWhere('p.enable = :status')
                ->setParameter('status', $status);
        }

        // Filter by signaled
        if ($signaled !== null) {
            $qb->andWhere('p.signaled = :signaled')
                ->setParameter('signaled', $signaled);
        }

        // Sorting
        if ($sortBy && in_array($sortBy, ['likeCount', 'dislikeCount', 'updatedAt'])) {
            $sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('p.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('p.updatedAt', 'DESC'); // Default sort
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the count of posts grouped by category for a pie chart.
     *
     * @return array
     */
    public function getPostsByCategory(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.categorie as category, COUNT(p.id) as count')
            ->groupBy('p.categorie')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the number of comments per post for a bar chart.
     *
     * @return array
     */
    public function getCommentsPerPost(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.titre as postTitle, COUNT(c.id) as commentCount')
            ->leftJoin('p.commentaires', 'c')
            ->groupBy('p.id')
            ->orderBy('commentCount', 'DESC')
            ->setMaxResults(10) // Limit to top 10 posts for better visualization
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the number of posts created over time (by month) for a line chart.
     *
     * @return array
     */
    public function getPostsOverTime(): array
    {
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('month', 'month');
        $rsm->addScalarResult('count', 'count');

        $query = $this->getEntityManager()->createNativeQuery(
            "SELECT DATE_FORMAT(p.updated_at, '%Y-%m') as month, COUNT(p.id) as count
             FROM post p
             GROUP BY month
             ORDER BY month ASC",
            $rsm
        );

        return $query->getResult();
    }

    /**
     * Get the count of signaled vs non-signaled posts for a pie chart.
     *
     * @return array
     */
    public function getSignaledPostsCount(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.signaled as signaled, COUNT(p.id) as count')
            ->groupBy('p.signaled')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the top 10 most liked posts for a bar chart.
     *
     * @return array
     */
    public function getMostLikedPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.titre as postTitle, p.likeCount as likeCount')
            ->orderBy('p.likeCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the top 10 most disliked posts for a bar chart.
     *
     * @return array
     */
    public function getMostDislikedPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.titre as postTitle, p.dislikeCount as dislikeCount')
            ->orderBy('p.dislikeCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}