<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** * @return User[] Returns an array of Users objects */
    public function findByUsername(string $username): array
    {
        return $this->createQueryBuilder("u")
            ->andWhere("u.username =: username")
            ->setParameter(' username', $username)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** * @return User[] Returns an array of User objects */
    public
    function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /** * @return User|null Returns a User object or null */
    public
    function findOneByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** * @return User|null Returns a User object or null */
    public
    function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

}

