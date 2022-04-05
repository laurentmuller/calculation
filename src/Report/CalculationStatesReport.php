<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Report;

use App\Entity\CalculationState;
use App\Pdf\PdfCellListenerInterface;
use App\Pdf\PdfCellListenerTrait;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of calculation states.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayReport<CalculationState>
 */
class CalculationStatesReport extends AbstractArrayReport implements PdfCellListenerInterface
{
    use PdfCellListenerTrait;

    /**
     * The started page.
     */
    private bool $started = false;

    /**
     * {@inheritdoc}
     *
     * @param string $orientation
     * @param mixed  $size
     * @param int    $rotation
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
                    ->setHeight(self::LINE_HEIGHT - 2 * $margin);
                $doc->rectangle($bounds, self::RECT_BOTH);

                return true;
            }
            $this->started = true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @param CalculationState[] $entities
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('calculationstate.list.title');

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumn(PdfColumn::left($this->trans('calculationstate.fields.code'), 20))
            ->addColumn(PdfColumn::left($this->trans('calculationstate.fields.description'), 80))
            ->addColumn(PdfColumn::center($this->trans('calculationstate.fields.editable'), 20, true))
            ->addColumn(PdfColumn::center($this->trans('calculationstate.fields.color'), 15, true))
            ->addColumn(PdfColumn::right($this->trans('calculationstate.fields.calculations'), 22, true))
            ->outputHeaders();

        foreach ($entities as $entity) {
            $table->startRow()
                ->add($entity->getCode())
                ->add($entity->getDescription())
                ->add($this->booleanFilter($entity->isEditable()))
                ->add(null, 1, $this->getColorStyle($entity))
                ->add(FormatUtils::formatInt($entity->countCalculations()))
                ->endRow();
        }

        // count
        return $this->renderCount($entities);
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
