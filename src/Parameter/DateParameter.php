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

class DateParameter implements ParameterInterface
{
    #[Parameter('archive_calculation')]
    private ?DatePoint $archive = null;
    #[Parameter('last_import')]
    private ?DatePoint $import = null;
    #[Parameter('update_calculation')]
    private ?DatePoint $updateCalculations = null;
    #[Parameter('update_product')]
    private ?DatePoint $updateProducts = null;

    public function getArchive(): ?DatePoint
    {
        return $this->archive;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_date';
    }

    public function getImport(): ?DatePoint
    {
        return $this->import;
    }

    public function getUpdateCalculations(): ?DatePoint
    {
        return $this->updateCalculations;
    }

    public function getUpdateProducts(): ?DatePoint
    {
        return $this->updateProducts;
    }

    public function setArchive(?DatePoint $archive = new DatePoint()): self
    {
        $this->archive = $archive;

        return $this;
    }

    public function setImport(?DatePoint $import = new DatePoint()): self
    {
        $this->import = $import;

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
