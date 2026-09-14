<?php

namespace TestedApp\Unhandled\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var list<string>|null */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $labels = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return list<string>|null */
    public function getLabels(): ?array
    {
        return $this->labels;
    }

    /** @param list<string>|null $labels */
    public function setLabels(?array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }
}
