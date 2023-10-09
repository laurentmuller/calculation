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

namespace App\Tests\EntityTrait;

use App\Entity\GlobalMargin;

/**
 * Trait to manage a global margin.
 */
trait GlobalMarginTrait
{
    private ?GlobalMargin $globalMargin = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteGlobalMargin(): void
    {
        if ($this->globalMargin instanceof GlobalMargin) {
            $this->globalMargin = $this->deleteEntity($this->globalMargin);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getGlobalMargin(float $minimum = 1.0, float $maximum = 100.0, float $margin = 0.1): GlobalMargin
    {
        if (!$this->globalMargin instanceof GlobalMargin) {
            $this->globalMargin = new GlobalMargin();
            $this->globalMargin->setMinimum($minimum)
                ->setMaximum($maximum)
                ->setMargin($margin);
            $this->addEntity($this->globalMargin);
        }

        return $this->globalMargin; // @phpstan-ignore-line
    }
}
