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

namespace App\Service;

use App\Entity\DigiPrint;
use App\Entity\DigiPrintItem;

/**
 * Service to compute a DigiPrint.
 *
 * @author Laurent Muller
 */
class DigiPrintService implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $blacklit;

    /**
     * @var float
     */
    private $blacklitAmount;

    /**
     * @var float
     */
    private $blacklitTotal;

    /**
     * @var DigiPrint|null
     */
    private $digiPrint;

    /**
     * @var float
     */
    private $overall;

    /**
     * @var bool
     */
    private $price;

    /**
     * @var float
     */
    private $priceAmount;

    /**
     * @var float
     */
    private $priceTotal;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var bool
     */
    private $replicating;

    /**
     * @var float
     */
    private $replicatingAmount;

    /**
     * @var float
     */
    private $replicatingTotal;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->quantity = 1;
        $this->price = true;
        $this->blacklit = false;
        $this->replicating = false;
        $this->reset();
    }

    /**
     * Compute values.
     */
    public function compute(): void
    {
        $this->reset();

        $quantity = $this->quantity;
        $digiPrint = $this->digiPrint;

        if (null !== $digiPrint && 0 !== $quantity) {
            if ($this->price && $item = $digiPrint->getItem(DigiPrintItem::TYPE_PRICE, $quantity)) {
                $this->priceAmount = $item->getAmount();
                $this->priceTotal = $this->priceAmount * $quantity;
            }
            if ($this->blacklit && $item = $digiPrint->getItem(DigiPrintItem::TYPE_BACKLIT, $quantity)) {
                $this->blacklitAmount = $item->getAmount();
                $this->blacklitTotal = $this->blacklitAmount * $quantity;
            }
            if ($this->replicating && $item = $digiPrint->getItem(DigiPrintItem::TYPE_REPLICATING, $quantity)) {
                $this->replicatingAmount = $item->getAmount();
                $this->replicatingTotal = $this->replicatingAmount * $quantity;
            }
        }

        $this->overall = $this->priceTotal + $this->blacklitTotal + $this->replicatingTotal;
    }

    /**
     * Gets the blacklit amount.
     */
    public function getBlacklitAmount(): float
    {
        return $this->blacklitAmount;
    }

    /**
     * Gets the blacklit total.
     */
    public function getBlacklitTotal(): float
    {
        return $this->blacklitTotal;
    }

    /**
     * Gets the DigiPrint.
     */
    public function getDigiPrint(): ?DigiPrint
    {
        return $this->digiPrint;
    }

    /**
     * Gets the overall.
     */
    public function getOverall(): float
    {
        return $this->overall;
    }

    /**
     * Gets the price amount.
     */
    public function getPriceAmount(): float
    {
        return $this->priceAmount;
    }

    /**
     * Gets the price total.
     */
    public function getPriceTotal(): float
    {
        return $this->priceTotal;
    }

    /**
     * Gets the quantity.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Gets the replicating amount.
     */
    public function getReplicatingAmount(): float
    {
        return $this->replicatingAmount;
    }

    /**
     * Gets the replicating total.
     */
    public function getReplicatingTotal(): float
    {
        return $this->replicatingTotal;
    }

    /**
     * Gets the blacklit state.
     */
    public function isBlacklit(): bool
    {
        return $this->blacklit;
    }

    /**
     * Gets the price state.
     */
    public function isPrice(): bool
    {
        return $this->price;
    }

    /**
     * Gets the replicating state.
     */
    public function isReplicating(): bool
    {
        return $this->replicating;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $id = $this->digiPrint ? $this->digiPrint->getId() : null;

        return [
            'digiPrint' => $id,

            'price' => $this->price,
            'priceAmount' => $this->priceAmount,
            'priceTotal' => $this->priceTotal,

            'blacklit' => $this->blacklit,
            'blacklitAmount' => $this->blacklitAmount,
            'blacklitTotal' => $this->blacklitTotal,

            'replicating' => $this->replicating,
            'replicatingAmount' => $this->replicatingAmount,
            'replicatingTotal' => $this->replicatingTotal,

            'quantity' => $this->quantity,
            'overall' => $this->overall,
        ];
    }

    /**
     * Sets the blacklit state.
     */
    public function setBlacklit(bool $blacklit): self
    {
        $this->blacklit = $blacklit;

        return $this;
    }

    /**
     * Sets the DigiPrint.
     */
    public function setDigiPrint(DigiPrint $digiPrint): self
    {
        $this->digiPrint = $digiPrint;

        return $this;
    }

    /**
     * Sets the price state.
     */
    public function setPrice(bool $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Sets the quantity.
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Sets the replicating state.
     */
    public function setReplicating(bool $replicating): self
    {
        $this->replicating = $replicating;

        return $this;
    }

    /**
     * Reset computed values.
     */
    private function reset(): void
    {
        $this->overall = 0.0;
        $this->priceAmount = $this->priceTotal = 0.0;
        $this->blacklitAmount = $this->blacklitTotal = 0.0;
        $this->replicatingAmount = $this->replicatingTotal = 0.0;
    }
}
