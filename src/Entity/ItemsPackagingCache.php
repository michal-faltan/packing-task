<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'items_packaging_cache')]
class ItemsPackagingCache
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $itemsAndPackagingsHash;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $boxWidth;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $boxHeight;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $boxLength;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $boxId;

    public function __construct
    (
        string $itemsAndPackagingsHash, 
        ?float $boxWidth,
        ?float $boxHeight,
        ?float $boxLength,
        ?int $boxId
    )
    {
        $this->itemsAndPackagingsHash = $itemsAndPackagingsHash;
        $this->boxWidth = $boxWidth;
        $this->boxHeight = $boxHeight;
        $this->boxLength = $boxLength;
        $this->boxId = $boxId;
    }

    public function isBoxDefined() : bool
    {
        return isset($this->boxWidth, $this->boxHeight, $this->boxLength);
    }

    public function getBoxDetailsAsArray() : array
    {
        return [
            'id' => $this->id,
            'w' => $this->boxWidth,
            'h' => $this->boxHeight,
            'd' => $this->boxLength,
        ];
    }
}