<?php

namespace App\Service;

use App\Entity\KeyValueStore;
use App\Entity\User;
use App\Repository\KeyValueStoreRepository;
use App\Transformer\KeyValueStoreTransformer;

class KeyValueStoreService
{
    private KeyValueStoreRepository $keyValueStoreRepository;
    private KeyValueStoreTransformer $keyValueStoreTransformer;

    public function __construct(KeyValueStoreRepository $keyValueStoreRepository, KeyValueStoreTransformer $keyValueStoreTransformer)
    {
        $this->keyValueStoreRepository = $keyValueStoreRepository;
        $this->keyValueStoreTransformer = $keyValueStoreTransformer;
    }

    public function save(KeyValueStore $keyValueStore): void
    {
        $this->keyValueStoreRepository->save($keyValueStore);
    }

    public function delete(KeyValueStore $keyValueStore): void
    {
        $this->keyValueStoreRepository->delete($keyValueStore);
    }

    public function getTransformedKeyValueStore(int $id): ?array
    {
        $keyValueStore = $this->keyValueStoreRepository->findById($id);
        if (!$keyValueStore) {
            return null;
        }

        return $this->keyValueStoreTransformer->transform($keyValueStore);
    }

    public function updateFromData(int $id, array $data): ?KeyValueStore
    {
        $keyValueStore = $this->keyValueStoreRepository->findById($id);
        if (!$keyValueStore) {
            return null;
        }

        $keyValueStore = $this->keyValueStoreTransformer->reverseTransform($data, $keyValueStore);
        $this->save($keyValueStore);

        return $keyValueStore;
    }

    public function findById(int $id): ?KeyValueStore
    {
        return $this->keyValueStoreRepository->findById($id);
    }

    public function findByUser(User $user): array
    {
        return $this->keyValueStoreRepository->findBy(['user' => $user]);
    }
}
