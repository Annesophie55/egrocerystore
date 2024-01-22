<?php

namespace App\Entity;

use App\Repository\NutritionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: NutritionRepository::class)]
#[Broadcast]
class Nutrition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $energy = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $saturatedFattyAcid = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $sugar = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $salt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $proteins = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $fibers = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $lipids = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $carbohydrates = null;

    #[ORM\OneToOne(mappedBy: 'nutrition', cascade: ['persist', 'remove'])]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnergy(): ?string
    {
        return $this->energy;
    }

    public function setEnergy(string $energy): static
    {
        $this->energy = $energy;

        return $this;
    }

    public function getSaturatedFattyAcid(): ?string
    {
        return $this->saturatedFattyAcid;
    }

    public function setSaturatedFattyAcid(string $saturatedFattyAcid): static
    {
        $this->saturatedFattyAcid = $saturatedFattyAcid;

        return $this;
    }

    public function getSugar(): ?string
    {
        return $this->sugar;
    }

    public function setSugar(string $sugar): static
    {
        $this->sugar = $sugar;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): static
    {
        $this->salt = $salt;

        return $this;
    }

    public function getProteins(): ?string
    {
        return $this->proteins;
    }

    public function setProteins(string $proteins): static
    {
        $this->proteins = $proteins;

        return $this;
    }

    public function getFibers(): ?string
    {
        return $this->fibers;
    }

    public function setFibers(string $fibers): static
    {
        $this->fibers = $fibers;

        return $this;
    }

    public function getLipids(): ?string
    {
        return $this->lipids;
    }

    public function setLipids(string $lipids): static
    {
        $this->lipids = $lipids;

        return $this;
    }

    public function getCarbohydrates(): ?string
    {
        return $this->carbohydrates;
    }

    public function setCarbohydrates(string $carbohydrates): static
    {
        $this->carbohydrates = $carbohydrates;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        // set the owning side of the relation if necessary
        if ($product->getNutrition() !== $this) {
            $product->setNutrition($this);
        }

        $this->product = $product;

        return $this;
    }
}
