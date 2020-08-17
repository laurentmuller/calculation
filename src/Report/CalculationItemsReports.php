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
use App\Pdf\PdfConstantsInterface;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;

/**
 * Abstract report to render calculations with empty or duplicate items.
 *
 * @author Laurent Muller
 */
abstract class CalculationItemsReports extends AbstractReport
{
    /**
     * The calculations and items to render.
     *
     * @var array
     */
    protected $calculations;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param string             $title       the report title to translate
     * @param string|null        $description the report description to translate
     */
    protected function __construct(AbstractController $controller, string $title, ?string $description = null)
    {
        parent::__construct($controller);
        $this->setTitleTrans($title);
        if ($description) {
            $this->setDescription($this->trans($description));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // calculations?
        if (empty($this->calculations)) {
            return false;
        }

        // new page
        $this->AddPage();

        // render
        $this->renderCalculations();

        // counters
        $parameters = [
            '%calculations%' => \count($this->calculations),
            '%items%' => $this->countItems($this->calculations),
        ];
        $text = $this->transCount($parameters);
        PdfStyle::getDefaultStyle()->apply($this);
        $margins = $this->setCellMargin(0);
        $this->Cell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE);
        $this->setCellMargin($margins);

        return true;
    }

    /**
     * Sets the calculations to render.
     */
    public function setCalculations(array $calculations): self
    {
        $this->calculations = $calculations;

        return $this;
    }

    /**
     * Gets the number of items.
     *
     * @param array $calculations the calculations
     */
    abstract protected function countItems(array $calculations): int;

    /**
     * Gets text for the given item.
     *
     * @param array $item the item to get text for
     *
     * @return string the formatted item
     */
    abstract protected function formatItem(array $item): string;

    /**
     * Translate the counters.
     *
     * @param array $parameters the parameters
     *
     * @return string the translated counters
     */
    abstract protected function transCount(array $parameters): string;

    /**
     * Render the calculations.
     */
    private function renderCalculations(): self
    {
        // table widths
        $margin = 5;
        $columns = 2;
        $headerLines = 4;
        $printableWidth = $this->getPrintableWidth();
        $width = ($printableWidth - ($margin * ($columns - 1))) / $columns;

        $maxY = 0;
        $index = 0;
        $x = $this->x;
        $y = $this->y;
        $idStyle = PdfStyle::getHeaderStyle();
        $itemStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());

        $maxLines = 0;
        foreach ($this->calculations as $calculation) {
            $maxLines = \max($maxLines, \count($calculation['items']));
        }

        foreach ($this->calculations as $calculation) {
            $col = $index % $columns;
            if (0 === $col) {
                $y = $this->y;
                $currentX = $x;
            } else {
                $this->y = $y;
                $currentX = $x + ($width + $margin) * $col;
            }

            $table = new PdfTableBuilder($this, false);
            $table->addColumn(PdfColumn::left(null, $width));

            $text = $this->localeId($calculation['id']);
            $this->singleLine($table, $currentX, $text, $idStyle);

            $text = $calculation['customer'] . PdfConstantsInterface::NEW_LINE;
            $text .= $calculation['description'] . PdfConstantsInterface::NEW_LINE;
            $text .= $this->localeDate($calculation['date']) . ' / ' . $calculation['stateCode'];
            $this->singleLine($table, $currentX, $text);

            // items
            $text = \array_reduce($calculation['items'], function (string $carry, array $item) {
                return $carry . $this->formatItem($item) . PdfConstantsInterface::NEW_LINE;
            }, '');
            $reminder = $maxLines - \count($calculation['items']);
            for ($i = 0; $i < $reminder; ++$i) {
                $text .= ' ' . PdfConstantsInterface::NEW_LINE;
            }
            $this->singleLine($table, $currentX, $text, $itemStyle);

            $maxY = \max($maxY, $this->y);

            // new row?
            ++$index;
            if (0 === $index % $columns) {
                $this->x = $x;
                $this->y = $maxY + $margin;
                if (!$this->isPrintable(($maxLines + $headerLines) * self::LINE_HEIGHT)) {
                    $this->AddPage();
                }
                $maxY = 0;
            }
        }

        if (0 === \count($this->calculations) % $columns) {
            $this->y -= $margin;
        }

        return $this;
    }

    /**
     * Render a single line.
     *
     * @param PdfTableBuilder $table the table to add text
     * @param float           $x     the x position
     * @param string          $text  the text to render
     * @param PdfStyle|null   $style the optional cell style
     */
    private function singleLine(PdfTableBuilder $table, float $x, string $text, ?PdfStyle $style = null): self
    {
        $this->x = $x;
        $table->singleLine($text, $style);

        return $this;
    }
}
