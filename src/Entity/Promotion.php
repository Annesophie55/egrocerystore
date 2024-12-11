<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[Broadcast]
#[ORM\HasLifecycleCallbacks]
class Promotion
{
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPersistOrUpdate(): void
    {
        $this->updateIsActive();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    private ?string $discountType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?float $rising = null;

    #[ORM\OneToMany(mappedBy: 'promotion', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\Column]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isManualOverride = false;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(?string $discountType): self
    {
        $this->discountType = $discountType;
        return $this;
    }

    public function getRising(): ?float
    {
        return $this->rising;
    }

    public function setRising(?float $rising): self
    {
        $this->rising = $rising;
        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setPromotion($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getPromotion() === $this) {
                $product->setPromotion(null);
            }
        }

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getIsManualOverride(): bool
    {
        return $this->isManualOverride;
    }

    public function setIsManualOverride(bool $isManualOverride): self
    {
        $this->isManualOverride = $isManualOverride;
        return $this;
    }

    public function updateIsActive(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->isManualOverride) {
            return;
        }

        $this->isActive = $this->startDate <= $now && $this->endDate >= $now;
    }

    public function activateManually(): void
    {
        $this->isManualOverride = true;
        $this->isActive = true;
    }

    public function deactivateManually(): void
    {
        $this->isManualOverride = true;
        $this->isActive = false;
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->isManualOverride) {
            return $this->isActive;
        }

        $now = new \DateTimeImmutable();

        return $this->startDate <= $now && $this->endDate >= $now;
    }
}
