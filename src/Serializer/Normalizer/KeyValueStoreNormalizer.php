<?php

namespace App\Serializer\Normalizer;

use App\Entity\KeyValueStore;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class KeyValueStoreNormalizer implements NormalizerInterface
{
    private const ALREADY_CALLED = 'KEY_VALUE_STORE_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        return [
            'id' => $object->getId(),
            'key' => $object->getKey(),
            'value' => $object->getValue(),
            'user_id' => $object->getUser()->getId(),
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof KeyValueStore;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [KeyValueStore::class => true];
    }
}
