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

use App\Controller\AbstractController;
use App\Entity\Customer;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Util\StringUtils;

/**
 * Report for the list of customers.
 *
 * @extends AbstractArrayReport<Customer>
 */
class CustomersReport extends AbstractArrayReport
{
    /**
     * The other group name.
     */
    private readonly string $other;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param Customer[]         $entities   the customers to export
     * @param bool               $grouped    true if the customers are grouped by the first letter
     */
    public function __construct(AbstractController $controller, array $entities, private readonly bool $grouped = true)
    {
        parent::__construct($controller, $entities, PdfDocumentOrientation::LANDSCAPE);
        $this->other = $this->trans('report.other');
    }

    /**
     * {@inheritdoc}
     *
     * @param Customer[] $entities
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('customer.list.title');

        // new page
        $this->AddPage();

        // create table
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left($this->trans('customer.fields.nameAndCompany'), 50),
                PdfColumn::left($this->trans('customer.fields.address'), 25),
                PdfColumn::left($this->trans('customer.fields.zipCity'), 25)
            )->outputHeaders();

        // grouped?
        if ($this->grouped) {
            $this->outputGrouped($table, $entities);
        } else {
            $this->outputList($table, $entities);
        }

        // count
        return $this->renderCount($entities);
    }

    private function firstChar(string $text): string
    {
        if ('' === $text) {
            return $this->other;
        }

        return \strtoupper(StringUtils::slug($text)[0]);
    }

    /**
     * @param Customer[] $entities
     *
     * @return array<string|int, Customer[]>
     */
    private function groupCustomers(array $entities): array
    {
        $result = [];
        foreach ($entities as $c) {
            $key = $this->firstChar($c->getNameAndCompany());
            $result[$key][] = $c;
        }

        \uksort($result, function (string $str1, string $str2) {
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

    private function outputCustomer(PdfGroupTableBuilder $table, Customer $customer): void
    {
        $table->addRow(
            $customer->getNameAndCompany(),
            $customer->getAddress(),
            $customer->getZipCity()
        );
    }

    /**
     * @param Customer[] $entities
     */
    private function outputGrouped(PdfGroupTableBuilder $table, array $entities): void
    {
        $groups = $this->groupCustomers($entities);
        foreach ($groups as $key => $customers) {
            $this->addBookmark((string) $key);
            $table->setGroupKey($key);
            foreach ($customers as $customer) {
                $this->outputCustomer($table, $customer);
            }
        }
    }

    /**
     * @param Customer[] $entities
     */
    private function outputList(PdfGroupTableBuilder $table, array $entities): void
    {
        foreach ($entities as $entity) {
            $this->outputCustomer($table, $entity);
        }
    }
}
