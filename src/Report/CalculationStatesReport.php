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
use App\Entity\CalculationState;
use App\Pdf\PdfCellListenerInterface;
use App\Pdf\PdfCellListenerTrait;
use App\Pdf\PdfColumn;
use App\Pdf\PdfConstantsInterface;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\Utils;

/**
 * Report for the list of calculation states.
 *
 * @author Laurent Muller
 */
class CalculationStatesReport extends AbstractReport implements PdfCellListenerInterface
{
    use PdfCellListenerTrait;

    /**
     * The started page.
     *
     * @var bool
     */
    private $started = false;

    /**
     * The calculation states to render.
     *
     * @var CalculationState[]
     */
    private $states;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('calculationstate.list.title');
    }

    /**
     * {@inheritdoc}
     */
    public function AddPage($orientation = '', $size = '', $rotation = 0): void
    {
        parent::AddPage($orientation, $size, $rotation);
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function onDrawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool
    {
        // color column?
        if (3 === $index) {
            if ($this->started) {
                $doc = $builder->getParent();
                $margin = $doc->getCellMargin();
                $bounds->inflateXY(-3 * $margin, -$margin)
                    ->setHeight(PdfConstantsInterface::LINE_HEIGHT - 2 * $margin);
                $doc->rectangle($bounds, self::RECT_BOTH);

                return true;
            }
            $this->started = true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // states?
        $states = $this->states;
        $count = \count($states);
        if (0 === $count) {
            return false;
        }

        // sort
        Utils::sortField($states, 'code');

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumn(PdfColumn::left($this->trans('calculationstate.fields.code'), 20))
            ->addColumn(PdfColumn::left($this->trans('calculationstate.fields.description'), 80))
            ->addColumn(PdfColumn::center($this->trans('calculationstate.fields.editable'), 20, true))
            ->addColumn(PdfColumn::center($this->trans('calculationstate.fields.color'), 15, true))
            ->outputHeaders();

        // states
        foreach ($states as $state) {
            $table->startRow()
                ->add($state->getCode())
                ->add($state->getDescription())
                ->add($this->booleanFilter($state->isEditable()))
                ->add(null, 1, $this->getColorStyle($state))
                ->endRow();
        }

        // count
        return $this->renderCount($count);
    }

    /**
     * Sets the categories to render.
     *
     * @param \App\Entity\CalculationState[] $states
     */
    public function setStates(array $states): self
    {
        $this->states = $states;

        return $this;
    }

    /**
     * Gets the cell style for the given state color.
     *
     * @param calculationState $state the state to get style for
     *
     * @return PdfStyle|null the style, if applicable, null otherwise
     */
    private function getColorStyle(CalculationState $state): ?PdfStyle
    {
        $color = PdfFillColor::create($state->getColor());
        if (null !== $color) {
            return PdfStyle::getCellStyle()->setFillColor($color);
        }

        return null;
    }
}
