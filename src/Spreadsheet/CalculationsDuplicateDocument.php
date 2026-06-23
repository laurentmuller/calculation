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
use App\Traits\DuplicateItemsTrait;

/**
 * Spreadsheet document for the list of calculations with duplicate items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationsDuplicateDocument extends AbstractCalculationItemsDocument
{
    use DuplicateItemsTrait;

    /**
     * @phpstan-param CalculationItemType[] $entities
     */
    public function __construct(DocumentHelperInterface $helper, array $entities)
    {
        parent::__construct($helper, $entities, 'duplicate.title');
        $this->setTranslatedDescription('duplicate.description');
    }
}
