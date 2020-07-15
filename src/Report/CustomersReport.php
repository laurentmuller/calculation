<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\Customer;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Utils\Utils;

/**
 * Report for the list of customers.
 *
 * @author Laurent Muller
 */
class CustomersReport extends AbstractReport
{
    /**
     * The customers to render.
     *
     * @var \App\Entity\Customer[]
     */
    private $customers;

    /**
     * The group customers by first letter.
     *
     * @var bool
     */
    private $grouped = true;

    /**
     * The other group name.
     *
     * @var string
     */
    private $other;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller, self::ORIENTATION_LANDSCAPE);
        $this->setTitleTrans('customer.list.title');
        $this->other = $controller->getTranslator()->trans('report.customer.other');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // customers?
        $customers = $this->customers;
        $count = \count($customers);
        if (0 === $count) {
            return false;
        }

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

        // sort
        Utils::sortField($customers, 'nameAndCompany');

        // grouped?
        if ($this->grouped) {
            $groups = $this->groupCustomers($customers);
            foreach ($groups as $name => $items) {
                $table->setGroupName((string) $name);
                foreach ($items as $customer) {
                    $this->outputCustomer($table, $customer);
                }
            }
        } else {
            foreach ($customers as $customer) {
                $this->outputCustomer($table, $customer);
            }
        }

        // count
        return $this->renderCount($count);
    }

    /**
     * Sets the customers to render.
     *
     * @param \App\Entity\Customer[] $customers
     */
    public function setCustomers(array $customers): self
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * Sets if the customers are grouped by the first letter.
     */
    public function setGrouped(bool $grouped): self
    {
        $this->grouped = $grouped;

        return $this;
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
        if (0 !== \strlen($text)) {
            return (string) \strtoupper($text[0]);
        }

        return $this->other;
    }

    /**
     * Groups customers by a the first character of the name or company.
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
