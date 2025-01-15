<?php

namespace App\Service;

use App\Entity\KeyValueStore;
use Doctrine\ORM\EntityManagerInterface;

class KeyValueStoreService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(KeyValueStore $keyValueStore): void
    {
        $this->entityManager->persist($keyValueStore);
        $this->entityManager->flush();
    }

    public function delete(KeyValueStore $keyValueStore): void
    {
        $this->entityManager->remove($keyValueStore);
        $this->entityManager->flush();
    }
}
