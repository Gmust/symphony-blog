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

    public function save(Post $post): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($post);
        $entityManager->flush();
    }

    public function delete(Post $post): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($post);
        $entityManager->flush();
    }

    /**
     * @return Post[] Returns an array of Post objects
     */
    public function findAllPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPostById(int $id): ?Post
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
