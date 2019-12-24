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
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;

/**
 * Report for calculations with invalid items.
 *
 * @author Laurent Muller
 */
abstract class CalculationItemsTableReport extends BaseReport
{
    /**
     * The items to render.
     *
     * @var array
     */
    private $items;

    /**
     * Constructor.
     *
     * @param BaseController $controller  the parent controller
     * @param string         $title       the title to translate
     * @param string         $description the description to translate
     */
    protected function __construct(BaseController $controller, string $title, string $description)
    {
        parent::__construct($controller, self::ORIENTATION_LANDSCAPE);
        $this->SetTitleTrans($title, [], true);
        $this->setDescription($this->trans($description));
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // calculations?
        if (empty($this->items)) {
            return false;
        }

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // items style
        $style = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());

        // add
        foreach ($this->items as $item) {
            $table->startRow()
                ->add($this->localeId($item['id']))
                ->add($this->localeDate($item['date']))
                ->add($item['stateCode'])
                ->add($item['customer'])
                ->add($item['description'])
                ->add($this->formatItems($item['items']), 1, $style)
                ->endRow();
        }
        PdfStyle::getDefaultStyle()->apply($this);

        // counters
        $parameters = [
            '%calculations%' => \count($this->items),
            '%items%' => $this->computeItemsCount($this->items),
        ];
        $text = $this->transCount($parameters);
        $this->Cell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE);

        return true;
    }

    /**
     * Sets the items to render.
     */
    public function setItems(?array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Compute the number of items.
     *
     * @param array $items the calculations
     *
     * @return int the number of items
     */
    abstract protected function computeItemsCount(array $items): int;

    /**
     * Formats the calculation items.
     *
     * @param array $items the calculation items
     *
     * @return string the formatted items
     */
    abstract protected function formatItems(array $items): string;

    /**
     * Translate the counters.
     *
     * @param array $parameters the parameters
     *
     * @return string the translated counters
     */
    abstract protected function transCount(array $parameters): string;

    /**
     * Creates the table.
     */
    private function createTable(): PdfTableBuilder
    {
        // create table
        $columns = [
            PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
            PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
            PdfColumn::left($this->trans('calculation.fields.state'), 12, false),
            PdfColumn::left($this->trans('calculation.fields.customer'), 35, false),
            PdfColumn::left($this->trans('calculation.fields.description'), 60, false),
            PdfColumn::left($this->trans('calculation.fields.items'), 45, false),
        ];

        $table = new PdfTableBuilder($this);

        return $table->addColumns($columns)->outputHeaders();
    }
}
