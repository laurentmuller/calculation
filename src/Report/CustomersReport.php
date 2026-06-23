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

namespace App\Report;

use App\Entity\Customer;
use App\Interfaces\DocumentHelperInterface;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Utils\StringUtils;
use fpdf\Enums\PdfOrientation;

/**
 * Report for the list of customers.
 *
 * @extends AbstractArrayReport<Customer>
 */
class CustomersReport extends AbstractArrayReport
{
    private readonly string $other;

    /**
     * @param Customer[] $entities the customers to export
     */
    public function __construct(DocumentHelperInterface $helper, array $entities)
    {
        parent::__construct($helper, $entities, PdfOrientation::LANDSCAPE);
        $this->setTranslatedTitle('customer.list.title');
        $this->other = $this->trans('report.other');
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $table = $this->createTable();
        $groups = $this->getGroupedCustomers($entities);
        foreach ($groups as $key => $customers) {
            $this->addBookmark((string) $key);
            $table->setGroupKey($key);
            foreach ($customers as $customer) {
                $this->outputCustomer($table, $customer);
            }
        }

        return $this->renderCount($table, $entities, 'counters.customers');
    }

    private function createTable(): PdfGroupTable
    {
        return PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                $this->leftColumn('customer.fields.nameAndCompany', 50),
                $this->leftColumn('customer.fields.address', 25),
                $this->leftColumn('customer.fields.zipCity', 25)
            )->outputHeaders();
    }

    private function getFirstChar(?string $text): string
    {
        if (StringUtils::isString($text)) {
            return \strtoupper(StringUtils::slug($text)[0]);
        }

        return $this->other;
    }

    /**
     * @param Customer[] $entities
     *
     * @return array<string|int, Customer[]>
     */
    private function getGroupedCustomers(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $key = $this->getFirstChar($entity->getNameAndCompany());
            $result[$key][] = $entity;
        }
        \uksort($result, function (string $str1, string $str2): int {
            if ($str1 === $this->other) {
                return -1;
            }
            if ($str2 === $this->other) {
                return 1;
            }

            return \strcasecmp($str1, $str2);
        });

        return $result;
    }

    private function outputCustomer(PdfGroupTable $table, Customer $customer): void
    {
        $table->addRow(
            $customer->getNameAndCompany(),
            $customer->getAddress(),
            $customer->getZipCity()
        );
    }
}
