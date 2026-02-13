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
use App\Repository\CalculationRepository;
use App\Traits\CalculationDocumentMarginTrait;
use App\Traits\MathTrait;

/**
 * Spreadsheet document for the list of calculations.
 *
 * @phpstan-import-type ExportType from CalculationRepository
 */
class CalculationsDocument extends AbstractDocument
{
    use CalculationDocumentMarginTrait;
    use MathTrait;

    private readonly float $minMargin;

    /**
     * @param AbstractController $controller the parent controller
     * @param iterable<array>    $entities   the calculations to render
     *
     * @phpstan-param iterable<ExportType> $entities
     */
    public function __construct(AbstractController $controller, private readonly iterable $entities)
    {
        parent::__construct($controller);
        $this->minMargin = $controller->getMinMargin();
    }

    #[\Override]
    public function render(): bool
    {
        $sheet = $this->start('calculation.list.title', true)
            ->getActiveSheet();

        $row = $this->renderHeaders($sheet);
        foreach ($this->entities as $entity) {
            $row = $this->renderEntity($sheet, $entity, $row);
        }
        $sheet->finish();

        return $row > 1;
    }

    /**
     * @phpstan-param ExportType $entity
     */
    private function renderEntity(WorksheetDocument $sheet, array $entity, int $row): int
    {
        $itemsTotal = $entity['itemsTotal'];
        $overallTotal = $entity['overallTotal'];
        $margins = $this->getSafeMargin($overallTotal, $itemsTotal);
        $sheet->setRowValues($row++, [
            $entity['id'],
            $entity['date'],
            $entity['code'],
            $entity['customer'],
            $entity['description'],
            $itemsTotal,
            $margins,
            $overallTotal,
        ]);

        return $row;
    }

    private function renderHeaders(WorksheetDocument $sheet): int
    {
        $marginFormat = $this->getMarginFormat($sheet, $this->minMargin);

        return $sheet->setHeaders([
            'calculation.fields.id' => HeaderFormat::id(),
            'calculation.fields.date' => HeaderFormat::date(),
            'calculation.fields.state' => HeaderFormat::instance(),
            'calculation.fields.customer' => HeaderFormat::instance(),
            'calculation.fields.description' => HeaderFormat::instance(),
            'calculationgroup.fields.amount' => HeaderFormat::amount(),
            'calculation.fields.margin' => HeaderFormat::percentCustom($marginFormat),
            'calculation.fields.total' => HeaderFormat::amount(),
        ]);
    }
}
