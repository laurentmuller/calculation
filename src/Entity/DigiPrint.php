<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents a digi print type.
 *
 * @ORM\Table(name="sy_DigiPrint")
 * @ORM\Entity(repositoryClass="App\Repository\DigiPrintRepository")
 * @UniqueEntity(fields="format", message="digiprint.unique_code")
 */
class DigiPrint extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", length=30)
     *
     * @var string
     */
    private $format;

    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $height;

    /**
     * @ORM\OneToMany(targetEntity=DigiPrintItem::class, mappedBy="digiPrint")
     *
     * @var Collection|DigiPrintItem[]
     */
    private $items;

    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $width;

    public function __construct()
    {
        $this->width = $this->height = 0;
        $this->items = new ArrayCollection();
    }

    public function addItem(DigiPrintItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setDigiPrint($this);
        }

        return $this;
    }

    /**
     * Gets the formatted width and height.
     */
    public function getDimension(): string
    {
        return \sprintf('%d x %d mm', $this->width, $this->height);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return \sprintf('%s - %d x %d mm', $this->format, $this->width, $this->height);
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Gets this item backlits.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemBacklits(): Collection
    {
        return $this->getItemsType(DigiPrintItem::TYPE_BACKLIT);
    }

    /**
     * Gets this item prices.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemPrices(): Collection
    {
        return $this->getItemsType(DigiPrintItem::TYPE_PRICE);
    }

    /**
     * Gets this item replicatings.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemReplicatings(): Collection
    {
        return $this->getItemsType(DigiPrintItem::TYPE_REPLICATING);
    }

    /**
     * @return Collection|DigiPrintItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Gets this items for the given type.
     *
     * @param int $type one of the item <code>TYPE_*</code> constants
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemsType(int $type): Collection
    {
        return $this->items->filter(function (DigiPrintItem $item) use ($type) {
            return $item->getType() === $type;
        });
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function removeItem(DigiPrintItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getDigiPrint() === $this) {
                $item->setDigiPrint(null);
            }
        }

        return $this;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }
}
