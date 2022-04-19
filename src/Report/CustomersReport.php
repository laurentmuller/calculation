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

/**
 * Report for the list of customers.
 *
 * @author Laurent Muller
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
        $columns = [
            PdfColumn::left($this->trans('customer.fields.nameAndCompany'), 50),
            PdfColumn::left($this->trans('customer.fields.address'), 25),
            PdfColumn::left($this->trans('customer.fields.zipCity'), 25),
        ];
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns($columns)
            ->outputHeaders();

        // grouped?
        if ($this->grouped) {
            $groups = $this->groupCustomers($entities);
            foreach ($groups as $name => $items) {
                $table->setGroupKey($name);
                foreach ($items as $entity) {
                    $this->outputCustomer($table, $entity);
                }
            }
        } else {
            foreach ($entities as $entity) {
                $this->outputCustomer($table, $entity);
            }
        }

        // count
        return $this->renderCount($entities);
    }

    /**
     * Gets the first character, in upper case, of the given text.
     *
     * @param string $text the text to get character for
     *
     * @return string the first character
     */
    private function getFirstChar(string $text): string
    {
        if ('' !== $text) {
            return \strtoupper($text[0]);
        }

        return $this->other;
    }

    /**
     * Groups customers by the first character of the name or company.
     *
     * @param Customer[] $customers the customers to group
     *
     * @return array<string, Customer[]> an array with first character as key, and corresponding customers as value
     */
    private function groupCustomers(array $customers): array
    {
        // group
        $result = [];
        foreach ($customers as $c) {
            $key = $this->getFirstChar($c->getNameAndCompany());
            $result[$key][] = $c;
        }

        // sort
        \uksort($result, function (string $str1, string $str2) {
            if ($str1 === $this->other) {
                return -1;
            }
            if ($str2 === $this->other) {
                return 1;
            }

            return \strcmp($str1, $str2);
        });

        return $result;
    }

    /**
     * Output a customer.
     *
     * @param PdfGroupTableBuilder $table    the table to write to
     * @param Customer             $customer the customer to output
     */
    private function outputCustomer(PdfGroupTableBuilder $table, Customer $customer): void
    {
        $table->startRow()
            ->add($customer->getNameAndCompany())
            ->add($customer->getAddress())
            ->add($customer->getZipCity())
            ->endRow();
    }
}
