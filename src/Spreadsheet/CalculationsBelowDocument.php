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

namespace App\Spreadsheet;

use App\Interfaces\DocumentHelperInterface;
use App\Repository\CalculationRepository;
use App\Utils\FormatUtils;

/**
 * Spreadsheet document for the list of calculations with margin below.
 *
 * @phpstan-import-type ExportType from CalculationRepository
 */
class CalculationsBelowDocument extends CalculationsDocument
{
    /**
     * @param iterable<array> $entities the calculations to render
     *
     * @phpstan-param iterable<ExportType> $entities
     */
    public function __construct(DocumentHelperInterface $helper, iterable $entities)
    {
        parent::__construct($helper, $entities);
        $margin = FormatUtils::formatPercent($helper->getMinMargin());
        $this->setTranslatedDescription('below.description', ['%margin%' => $margin]);
    }

    #[\Override]
    protected function start(string $title, bool $landscape = false): static
    {
        return parent::start('below.title', $landscape);
    }
}
