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
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;

/**
 * Report for php.ini.
 *
 * @author Laurent Muller
 */
class PhpIniReport extends AbstractReport
{
    /**
     * The content to export.
     *
     * @var array
     */
    private $content;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
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

        // sort keys
        \ksort($content, SORT_STRING | SORT_FLAG_CASE);

        // new page
        $this->AddPage();

        // create table
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Directive', 40))
            ->addColumn(PdfColumn::left('Local Value', 30))
            ->addColumn(PdfColumn::left('Master Value', 30))
            ->outputHeaders();

        // output content
        foreach ($content as $key => $value) {
            $table->setGroupKey($key);
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
            return \ucfirst(\json_encode($var));
        } else {
            return \htmlspecialchars_decode((string) $var);
        }
    }

    /**
     * Gets the cell style for the given value.
     *
     * @param string $var the value to get style for
     *
     * @return PdfStyle|null the style, if applicable; null otherwise
     */
    private function getCellStyle(string $var): ?PdfStyle
    {
        if (\preg_match('/#[0-9A-Fa-f]{6}/i', $var) && $color = PdfTextColor::create($var)) {
            return PdfStyle::getCellStyle()->setTextColor($color);
        } elseif ('No value' === $var) {
            return PdfStyle::getCellStyle()
                ->setTextColor(PdfTextColor::create('#7F7F7F'))
                ->setFontItalic(true);
        }

        return null;
    }

    /**
     * Output the given entries to the given table.
     *
     * @param PdfGroupTableBuilder $table   the table to update
     * @param array                $entries the entries to output
     */
    private function outputEntries(PdfGroupTableBuilder $table, array $entries): void
    {
        $this->sortEntries($entries);

        foreach ($entries as $key => $entry) {
            if (\is_array($entry)) {
                $local = $this->convert(\reset($entry));
                $master = $this->convert(\end($entry));
                $table->startRow()
                    ->add($this->convert($key))
                    ->add($local, 1, $this->getCellStyle($local))
                    ->add($master, 1, $this->getCellStyle($master))
                    ->endRow();
            } else {
                $value = $this->convert($entry);
                $table->startRow()
                    ->add($this->convert($key))
                    ->add($value, 2, $this->getCellStyle($value))
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
        \uksort($entries, function (string $a, string $b) use ($entries) {
            $isArrayA = \is_array($entries[$a]);
            $isArrayB = \is_array($entries[$b]);
            if ($isArrayA !== $isArrayB) {
                return $isArrayA <=> $isArrayB;
            } else {
                return \strcasecmp($a, $b);
            }
        });
    }
}
