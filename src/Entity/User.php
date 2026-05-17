<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Dto\RegisterInput;
use App\State\RegisterProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]

#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    operations: [

        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        ),

        new Get(
            security: "object == user or is_granted('ROLE_ADMIN')"
        ),

        new Post(
            uriTemplate: '/register',
            input: RegisterInput::class,
            inputFormats: ['json' => ['application/json']],
            processor: RegisterProcessor::class
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[Groups(['user:read'])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?bool $isVerified = false;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\OneToMany(
        targetEntity: Theme::class,
        mappedBy: 'createdBy',
        orphanRemoval: true
    )]
    private Collection $createdTheme;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\OneToMany(
        targetEntity: Theme::class,
        mappedBy: 'updatedBy'
    )]
    private Collection $themes;

    /**
     * @var Collection<int, Purchase>
     */
    #[ORM\OneToMany(targetEntity: Purchase::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $purchases;

    public function __construct()
    {
        $this->createdTheme = new ArrayCollection();
        $this->themes = new ArrayCollection();
        $this->purchases = new ArrayCollection();
    }

    // --------------------------------------------------
    // LIFECYCLE
    // --------------------------------------------------

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;

        if ($this->isVerified === null) {
            $this->isVerified = false;
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --------------------------------------------------
    // SECURITY
    // --------------------------------------------------

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    // --------------------------------------------------
    // GETTERS / SETTERS
    // --------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Theme>
     */
    public function getCreatedTheme(): Collection
    {
        return $this->createdTheme;
    }

    public function addCreatedTheme(Theme $createdTheme): static
    {
        if (!$this->createdTheme->contains($createdTheme)) {
            $this->createdTheme->add($createdTheme);
            $createdTheme->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedTheme(Theme $createdTheme): static
    {
        if ($this->createdTheme->removeElement($createdTheme)) {
            if ($createdTheme->getCreatedBy() === $this) {
                $createdTheme->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Theme>
     */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(Theme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
            $theme->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        if ($this->themes->removeElement($theme)) {
            if ($theme->getUpdatedBy() === $this) {
                $theme->setUpdatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Purchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Purchase $purchase): static
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases->add($purchase);
            $purchase->setUser($this);
        }

        return $this;
    }

    public function removePurchase(Purchase $purchase): static
    {
        if ($this->purchases->removeElement($purchase)) {
            // set the owning side to null (unless already changed)
            if ($purchase->getUser() === $this) {
                $purchase->setUser(null);
            }
        }

        return $this;
    }
}