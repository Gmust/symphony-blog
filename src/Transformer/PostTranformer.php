<?php

namespace App\Transformer;

use App\Entity\Post;

class PostTranformer
{
    /**
     * Transforms a Post entity into an associative array.
     *
     * @param Post $post
     * @return array
     */
    public function transform(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $post->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'user' => $post->getUser()->getId(),
        ];
    }

    /**
     * Transforms an associative array into a Post entity.
     *
     * @param array $data
     * @param Post $post
     * @return Post
     */
    public function reverseTransform(array $data, Post $post): Post
    {
        $post->setTitle($data['title']);
        $post->setContent($data['content']);

        return $post;
    }
}
