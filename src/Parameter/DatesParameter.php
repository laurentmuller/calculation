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

namespace App\Parameter;

use App\Attribute\Parameter;
use Symfony\Component\Clock\DatePoint;

class DatesParameter implements ParameterInterface
{
    #[Parameter('archive_calculation')]
    private ?DatePoint $archiveCalculations = null;
    #[Parameter('last_import')]
    private ?DatePoint $lastImport = null;
    #[Parameter('update_calculation')]
    private ?DatePoint $updateCalculations = null;
    #[Parameter('update_product')]
    private ?DatePoint $updateProducts = null;

    public function getArchiveCalculations(): ?DatePoint
    {
        return $this->archiveCalculations;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_dates';
    }

    public function getLastImport(): ?DatePoint
    {
        return $this->lastImport;
    }

    public function getUpdateCalculations(): ?DatePoint
    {
        return $this->updateCalculations;
    }

    public function getUpdateProducts(): ?DatePoint
    {
        return $this->updateProducts;
    }

    public function setArchiveCalculations(?DatePoint $archiveCalculations = new DatePoint()): self
    {
        $this->archiveCalculations = $archiveCalculations;

        return $this;
    }

    public function setLastImport(?DatePoint $lastImport = new DatePoint()): self
    {
        $this->lastImport = $lastImport;

        return $this;
    }

    public function setUpdateCalculations(?DatePoint $updateCalculations = new DatePoint()): self
    {
        $this->updateCalculations = $updateCalculations;

        return $this;
    }

    public function setUpdateProducts(?DatePoint $updateProducts = new DatePoint()): self
    {
        $this->updateProducts = $updateProducts;

        return $this;
    }
}
