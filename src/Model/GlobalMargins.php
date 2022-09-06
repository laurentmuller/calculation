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
class GlobalMargins
{
    use ValidateMarginsTrait;

    /**
     * @var Collection<int, GlobalMargin>
     */
    #[Assert\Valid]
    private Collection $margins;

    /**
     * Constructor.
     *
     * @param GlobalMargin[] $margins
     */
    public function __construct(array $margins = [])
    {
        $this->margins = new ArrayCollection($margins);
    }

    /**
     * Add a margin.
     */
    public function addMargin(GlobalMargin $margin): self
    {
        if (!$this->margins->contains($margin)) {
            $this->margins[] = $margin;
        }

        return $this;
    }

    /**
     * Get margins.
     *
     * @return Collection<int, GlobalMargin>
     */
    public function getMargins(): Collection
    {
        return $this->margins;
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
     * @param GlobalMargin[] $margins
     */
    public function setMargins(array $margins): self
    {
        $this->margins = new ArrayCollection($margins);

        return $this;
    }
}
