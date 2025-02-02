<?php

namespace App\Serializer\Normalizer;

use App\Entity\Post;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PostNormalizer implements NormalizerInterface
{
    private const ALREADY_CALLED = 'POST_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        return [
            'id' => $object->getId(),
            'title' => $object->getTitle(),
            'content' => $object->getContent(),
            'author' => [
                'id' => $object->getUser()->getId(),
                'username' => $object->getUser()->getUsername(),
            ],
            'created_at' => $object->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Post;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Post::class => true];
    }
}
