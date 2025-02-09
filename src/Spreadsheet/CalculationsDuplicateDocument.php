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

use App\Controller\AbstractController;
use App\Traits\DuplicateItemsTrait;

/**
 * Spreadsheet document for the list of calculations with duplicate items.
 *
 * @psalm-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationsDuplicateDocument extends AbstractCalculationItemsDocument
{
    use DuplicateItemsTrait;

    /**
     * @psalm-param CalculationItemType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'duplicate.title');
        $this->setDescriptionTrans('duplicate.description');
    }
}
