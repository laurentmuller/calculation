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

use App\Controller\BaseController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;

class PhpIniReport extends BaseReport
{
    /**
     * The data to export.
     *
     * @var array
     */
    private $content;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->SetTitle(\php_ini_loaded_file() ?? 'php.ini');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // content?
        $content = $this->content;
        if (empty($content)) {
            return false;
        }

        // sort
        \ksort($content, SORT_STRING | SORT_FLAG_CASE);

        // new page
        $this->AddPage();

        // table
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Directive', 40))
            ->addColumn(PdfColumn::left('Local Value', 30))
            ->addColumn(PdfColumn::left('Master Value', 30))
            ->outputHeaders();

        // output
        foreach ($content as $key => $value) {
            $table->setGroupName($key);
            $this->outputEntries($table, $value);
        }

        return true;
    }

    /**
     * Sets the content to export.
     */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Converts the given variable to a string.
     *
     * @param mixed $var the variable to convert
     *
     * @return string the converted variable
     */
    private function convert($var): string
    {
        if (\is_bool($var)) {
            return \json_encode($var);
        } else {
            return \htmlspecialchars_decode((string) $var);
        }
    }

    /**
     * Output the given entries to the table.
     *
     * @param PdfGroupTableBuilder $table   the table to update
     * @param array                $entries the entries to output
     */
    private function outputEntries(PdfGroupTableBuilder $table, array $entries): void
    {
        $this->sortEntries($entries);
        foreach ($entries as $key => $entry) {
            if (\is_array($entry)) {
                $table->startRow()
                    ->add($this->convert($key))
                    ->add($this->convert(\reset($entry)))
                    ->add($this->convert(\end($entry)))
                    ->endRow();
            } else {
                $table->startRow()
                    ->add($this->convert($key))
                    ->add($this->convert($entry), 2)
                    ->endRow();
            }
        }
    }

    /**
     * Sorts the given entries.
     *
     * @param array $entries the entries to sort
     */
    private function sortEntries(array &$entries): void
    {
        \uksort($entries, function ($a, $b) use ($entries) {
            $isArrayA = \is_array($entries[$a]) ? 1 : 0;
            $isArrayB = \is_array($entries[$b]) ? 1 : 0;
            if ($isArrayA !== $isArrayB) {
                return $isArrayA <=> $isArrayB;
            } else {
                return \strcasecmp($a, $b);
            }
        });
    }
}
