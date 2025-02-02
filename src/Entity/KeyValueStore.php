<?php

namespace App\Entity;

use App\Repository\KeyValueStoreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KeyValueStoreRepository::class)]
class KeyValueStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['key_value:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'keyValueStores')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['key_value:read'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['key_value:read'])]
    private ?string $key = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['key_value:read'])]
    private ?array $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getValue(): ?array
    {
        return $this->value;
    }

    public function setValue(array $value): self
    {
        $this->value = $value;
        return $this;
    }
}
