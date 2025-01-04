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

    public function findByEntityId(int $entityId): ?KeyValueStore
    {
        return $this->createQueryBuilder("k")
            ->andWhere('k.entityId = :entityId')
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function saveKeyValue(int $entityId, array $data): void
    {
        $keyValueStore = $this->findByEntityId($entityId);

        if (!$keyValueStore) {
            $keyValueStore = new KeyValueStore();
            $keyValueStore->setEntityId($entityId);
        }

        $keyValueStore->setData($data);

        $this->getEntityManager()->persist($keyValueStore);
        $this->getEntityManager()->flush();
    }


    public function updateKeyValue(int $entityId, string $key, $value): void
    {
        $keyValueStore = $this->findByEntityId($entityId);

        if ($keyValueStore) {
            $data = $keyValueStore->getData();
            $data[$key] = $value;
            $keyValueStore->setData($data);

            $this->getEntityManager()->persist($keyValueStore);
            $this->getEntityManager()->flush();
        }
    }

    public function deleteKeValue(int $entityId, string $key): void
    {
        $keyValueStore = $this->findByEntityId($entityId);

        if ($keyValueStore) {
            $data = $keyValueStore->getData();
            unset($data[$key]);
            $keyValueStore->setData($data);

            $this->getEntityManager()->persist($keyValueStore);
            $this->getEntityManager()->flush();
        }
    }
}
