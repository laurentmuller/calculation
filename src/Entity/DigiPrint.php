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

use App\Util\FormatUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @ORM\OneToMany(targetEntity=DigiPrintItem::class, mappedBy="digiPrint", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"minimum": "ASC"})
     * @Assert\Valid
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

    /**
     * Add an item.
     *
     * @param DigiPrintItem $item the item to add
     */
    public function addItem(DigiPrintItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setDigiPrint($this);
        }

        return $this;
    }

    /**
     * Gets the amount for the given type and quantity.
     *
     * @param int $type     one of the item <code>TYPE_*</code> constants
     * @param int $quantity the quantity to get amount for
     *
     * @return float the amount, if item found; 0 otherwise
     */
    public function getAmount(int $type, int $quantity): float
    {
        $item = $this->getItem($type, $quantity);

        return $item ? $item->getAmountQuantity($quantity) : 0;
    }

    /**
     * Gets the blackit amount for the given quantity.
     *
     * @param int $quantity the quantity to get amount for
     *
     * @return float the amount, if item found; 0 otherwise
     */
    public function getAmountBlackit(int $quantity): float
    {
        return $this->getAmount(DigiPrintItem::TYPE_BACKLIT, $quantity);
    }

    /**
     * Gets the price amount for the given quantity.
     *
     * @param int $quantity the quantity to get amount for
     *
     * @return float the amount, if item found; 0 otherwise
     */
    public function getAmountPrice(int $quantity): float
    {
        return $this->getAmount(DigiPrintItem::TYPE_PRICE, $quantity);
    }

    /**
     * Gets the replicating amount for the given quantity.
     *
     * @param int $quantity the quantity to get amount for
     *
     * @return float the amount, if item found; 0 otherwise
     */
    public function getAmountReplicating(int $quantity): float
    {
        return $this->getAmount(DigiPrintItem::TYPE_REPLICATING, $quantity);
    }

    /**
     * Gets the formatted width and height.
     */
    public function getDimension(): string
    {
        $width = FormatUtils::formatInt($this->width);
        $height = FormatUtils::formatInt($this->height);

        return \sprintf('%s x %s mm', $width, $height);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return \sprintf('%s - %s', $this->format, $this->getDimension());
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
     * Gets the item for the given type and quantity.
     *
     * @param int $type     one of the item <code>TYPE_*</code> constants
     * @param int $quantity the quantity to get item for
     *
     * @return DigiPrintItem the item, if found; null otherwise
     */
    public function getItem(int $type, int $quantity): ?DigiPrintItem
    {
        /** @var DigiPrintItem $item */
        foreach ($this->getItems($type) as $item) {
            if ($item->contains($quantity)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Gets items.
     *
     * @param int|null $type one of the item <code>TYPE_*</code> constants to return or null to return all items
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItems(int $type = null): Collection
    {
        if (null !== $type) {
            return $this->items->filter(function (DigiPrintItem $item) use ($type) {
                return $item->getType() === $type;
            });
        }

        return $this->items;
    }

    /**
     * Gets this backlit items.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemsBacklit(): Collection
    {
        return $this->getItems(DigiPrintItem::TYPE_BACKLIT);
    }

    /**
     * Gets this price items.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemsPrice(): Collection
    {
        return $this->getItems(DigiPrintItem::TYPE_PRICE);
    }

    /**
     * Gets this replicating items.
     *
     * @return Collection|DigiPrintItem[]
     */
    public function getItemsReplicating(): Collection
    {
        return $this->getItems(DigiPrintItem::TYPE_REPLICATING);
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Returns if this contains one or more backlits.
     *
     * @return bool true if contains backlits
     */
    public function hasBacklits(): bool
    {
        return !$this->getItemsBacklit()->isEmpty();
    }

    /**
     * Returns if this contains one or more prices.
     *
     * @return bool true if contains prices
     */
    public function hasPrices(): bool
    {
        return !$this->getItemsPrice()->isEmpty();
    }

    /**
     * Returns if this contains one or more replicatings.
     *
     * @return bool true if contains replicatings
     */
    public function hasReplicatings(): bool
    {
        return !$this->getItemsReplicating()->isEmpty();
    }

    /**
     * Remove an item.
     *
     * @param DigiPrintItem $item the item to remove
     */
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

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateItems($this->getItemsPrice(), $context);
        $this->validateItems($this->getItemsBacklit(), $context);
        $this->validateItems($this->getItemsReplicating(), $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->format,
            FormatUtils::formatInt($this->width),
            FormatUtils::formatInt($this->height),
        ];
    }

    private function validateItems(Collection $items, ExecutionContextInterface $context): void
    {
        // items?
        if ($items->isEmpty()) {
            return;
        }

        $lastMin = null;
        $lastMax = null;

        /** @var DigiPrintItem $item */
        foreach ($items as $key => $item) {
            // get values
            $min = $item->getMinimum();
            $max = $item->getMaximum();

            if (null === $lastMin) {
                // first time
                $lastMin = $min;
                $lastMax = $max;
            } elseif ($min <= $lastMin) {
                // the minimum is smaller than the previous maximum
                $context->buildViolation('digiprint.minimum_overlap')
                    ->atPath("items[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($min >= $lastMin && $min < $lastMax) {
                // the minimum is overlapping the previous margin
                $context->buildViolation('digiprint.minimum_overlap')
                    ->atPath("items[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($max > $lastMin && $max < $lastMax) {
                // the maximum is overlapping the previous margin
                $context->buildViolation('digiprint.maximum_overlap')
                    ->atPath("items[$key].maximum")
                    ->addViolation();
                break;
            } elseif ($min !== $lastMax) {
                // the minimum is not equal to the previous maximum
                $context->buildViolation('digiprint.minimum_discontinued')
                    ->atPath("items[$key].minimum")
                    ->addViolation();
                break;
            } else {
                // copy
                $lastMin = $min;
                $lastMax = $max;
            }
        }
    }
}
