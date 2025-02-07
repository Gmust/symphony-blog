<?php

namespace App\Transformer;

use App\Entity\User;

class UserTransformer
{
    /**
     * Transforms a User entity into an associative array.
     *
     * @param User $user
     * @return array
     */
    public function transform(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'profilePicture' => $user->getProfilePicture(),
            'posts' => array_map(function ($post) {
                return $post->getId();
            }, $user->getPosts()->toArray()),
            'keyValueStores' => array_map(function ($keyValueStore) {
                return $keyValueStore->getId();
            }, $user->getKeyValueStores()->toArray()),
        ];
    }

    /**
     * Transforms an associative array into a User entity.
     *
     * @param array $data
     * @param User $user
     * @return User
     */
    public function reverseTransform(array $data, User $user): User
    {
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['profilePicture'])) {
            $user->setProfilePicture($data['profilePicture']);
        }

        return $user;
    }
}
