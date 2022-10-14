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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Abstract Spreadsheet document for the list of calculations with invalid items.
 *
 * @extends AbstractArrayDocument<array{
 *      id: int,
 *      date: \DateTimeInterface,
 *      stateCode: string,
 *      customer: string,
 *      description: string,
 *      items: array<array{
 *          description: string,
 *          quantity: float,
 *          price: float,
 *          count: int}>
 *      }>
 */
abstract class AbstractCalculationItemsDocument extends AbstractArrayDocument
{
    /**
     * Constructor.
     *
     * @psalm-param array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }> $entities
     */
    public function __construct(AbstractController $controller, array $entities, string $title)
    {
        parent::__construct($controller, $entities);
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start((string) $this->title, true);

        // red color and word wrap for items
        $this->setForeground(6, Color::COLOR_RED)
            ->setWrapText(6);

        // headers
        $row = $this->setHeaderValues([
            'calculation.fields.id' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'calculation.fields.date' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'calculation.fields.state' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculation.fields.customer' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculation.fields.description' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'calculationgroup.fields.items' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
        ]);

        // formats
        $this->setFormatId(1)
            ->setFormatDate(2);

        // rows
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity['id'],
                $entity['date'],
                $entity['stateCode'],
                $entity['customer'],
                $entity['description'],
                $this->formatItems($entity['items']),
            ]);
        }

        $this->finish();

        return true;
    }

    /**
     * Formats the calculation items.
     *
     * @param array $items the calculation items
     *
     * @return string the formatted items
     *
     * @psalm-param array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}> $items
     */
    abstract protected function formatItems(array $items): string;
}
