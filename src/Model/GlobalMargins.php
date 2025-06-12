<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Model;

use App\Entity\GlobalMargin;
use App\Traits\ValidateMarginsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Object to edit global margins.
 */
class GlobalMargins implements \Countable
{
    /**
     * @use ValidateMarginsTrait<int, GlobalMargin>
     */
    use ValidateMarginsTrait;

    /**
     * @param Collection<int, GlobalMargin> $margins
     */
    public function __construct(
        #[Assert\Valid]
        private readonly Collection $margins
    ) {
    }

    /**
     * Add a margin.
     */
    public function addMargin(GlobalMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins->add($margin);
        }

        return $this;
    }

    #[\Override]
    public function count(): int
    {
        return $this->margins->count();
    }

    /**
     * Get margins.
     *
     * @return Collection<int, GlobalMargin>
     */
    #[\Override]
    public function getMargins(): Collection
    {
        return $this->margins;
    }

    /**
     * @param GlobalMargin[] $margins
     */
    public static function instance(array $margins = []): self
    {
        return new self(new ArrayCollection($margins));
    }

    /**
     * Remove a margin.
     */
    public function removeMargin(GlobalMargin $margin): self
    {
        $this->margins->removeElement($margin);

        return $this;
    }

    /**
     * Gets margins as an array.
     *
     * @return array<int, GlobalMargin>
     */
    public function toArray(): array
    {
        return $this->margins->toArray();
    }
}
