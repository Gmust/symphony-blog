<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class KeyValueTransformer implements DataTransformerInterface
{
    public function transform($value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return implode(', ', $value);
    }

    public function reverseTransform($value): mixed
    {
        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return array_map('trim', explode(',', $value));
    }
}
