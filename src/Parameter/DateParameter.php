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
use App\Utils\DateUtils;

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

    public function setArchive(?\DateTimeInterface $archive = null): self
    {
        $this->archive = $archive ?? DateUtils::createDateTime();

        return $this;
    }

    public function setImport(?\DateTimeInterface $import = null): self
    {
        $this->import = $import ?? DateUtils::createDateTime();

        return $this;
    }

    public function setUpdateCalculations(?\DateTimeInterface $updateCalculations = null): self
    {
        $this->updateCalculations = $updateCalculations ?? DateUtils::createDateTime();

        return $this;
    }

    public function setUpdateProducts(?\DateTimeInterface $updateProducts = null): self
    {
        $this->updateProducts = $updateProducts ?? DateUtils::createDateTime();

        return $this;
    }
}
