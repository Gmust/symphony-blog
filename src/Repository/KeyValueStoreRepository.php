<?php

namespace App\Repository;

use App\Entity\KeyValueStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeyValueStore>
 */
class KeyValueStoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeyValueStore::class);
    }

    public function findById(int $id): ?KeyValueStore
    {
        return $this->find($id);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('k.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(KeyValueStore $keyValueStore): void
    {
        $this->getEntityManager()->persist($keyValueStore);
        $this->getEntityManager()->flush();
    }

    public function update(KeyValueStore $keyValueStore, string $key, $value): void
    {
        $data = $keyValueStore->getValue();
        $data[$key] = $value;
        $keyValueStore->setValue($data);

        $this->save($keyValueStore);
    }

    public function delete(KeyValueStore $keyValueStore): void
    {
        $this->getEntityManager()->remove($keyValueStore);
        $this->getEntityManager()->flush();
    }
}
