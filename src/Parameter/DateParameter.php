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

class DateParameter implements ParameterInterface
{
    #[Parameter('archive_calculation')]
    private ?\DateTimeInterface $archive = null;
    #[Parameter('last_import')]
    private ?\DateTimeInterface $import = null;
    #[Parameter('update_calculation')]
    private ?\DateTimeInterface $updateCalculations = null;
    #[Parameter('update_product')]
    private ?\DateTimeInterface $updateProducts = null;

    public function getArchive(): ?\DateTimeInterface
    {
        return $this->archive;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_date';
    }

    public function getImport(): ?\DateTimeInterface
    {
        return $this->import;
    }

    public function getUpdateCalculations(): ?\DateTimeInterface
    {
        return $this->updateCalculations;
    }

    public function getUpdateProducts(): ?\DateTimeInterface
    {
        return $this->updateProducts;
    }

    public function setArchive(\DateTimeInterface $archive = new \DateTime()): self
    {
        $this->archive = $archive;

        return $this;
    }

    public function setImport(\DateTimeInterface $import = new \DateTime()): self
    {
        $this->import = $import;

        return $this;
    }

    public function setUpdateCalculations(\DateTimeInterface $updateCalculations = new \DateTime()): self
    {
        $this->updateCalculations = $updateCalculations;

        return $this;
    }

    public function setUpdateProducts(\DateTimeInterface $updateProducts = new \DateTime()): self
    {
        $this->updateProducts = $updateProducts;

        return $this;
    }
}
