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

/**
 * Abstract query with simulating (no flush changes in the database) property.
 */
abstract class AbstractSimulateQuery
{
    private bool $simulate = true;

    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    public function setSimulate(bool $simulate): static
    {
        $this->simulate = $simulate;

        return $this;
    }
}
