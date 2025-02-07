<?php

namespace App\Transformer;

use App\Entity\KeyValueStore;

class KeyValueStoreTransformer
{
    /**
     * Transforms a KeyValueStore entity into an associative array.
     *
     * @param KeyValueStore $keyValueStore
     * @return array
     */
    public function transform(KeyValueStore $keyValueStore): array
    {
        return [
            'id' => $keyValueStore->getId(),
            'user' => $keyValueStore->getUser()->getId(),
            'key' => $keyValueStore->getKey(),
            'value' => $keyValueStore->getValue(),
        ];
    }

    /**
     * Transforms an associative array into a KeyValueStore entity.
     *
     * @param array $data
     * @param KeyValueStore $keyValueStore
     * @return KeyValueStore
     */
    public function reverseTransform(array $data, KeyValueStore $keyValueStore): KeyValueStore
    {
        $keyValueStore->setKey($data['key']);
        $keyValueStore->setValue($data['value']);

        return $keyValueStore;
    }
}
