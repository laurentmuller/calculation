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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Report for a calculation.
 *
 * @author Laurent Muller
 */
class CalculationReport extends AbstractReport
{
    /**
     * The calculation.
     */
    private Calculation $calculation;

    /**
     * The minimum margin.
     */
    private float $minMargin;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     */
    public function __construct(AbstractController $controller, Calculation $calculation)
    {
        parent::__construct($controller);
        $this->calculation = $calculation;
        $this->minMargin = $controller->getApplication()->getMinMargin();
    }

    /**
     * Gets the calculation.
     */
    public function getCalculation(): Calculation
    {
        return $this->calculation;
    }

    /**
     * Gets the minimum allowed margin.
     */
    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    /**
     * Gets the translator.
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function Header(): void
    {
        parent::Header();
        $this->renderCalculation();
        $this->Ln(3);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $calculation = $this->calculation;

        // update title
        if ($calculation->isNew()) {
            $this->setTitleTrans('calculation.add.title');
        } else {
            $id = FormatUtils::formatId($calculation->getId());
            $this->setTitleTrans('calculation.edit.title', ['%id%' => $id], true);
        }

        // new page
        $this->AddPage();

        // empty?
        if ($calculation->isEmpty()) {
            $this->resetStyle()->Ln();
            $message = $this->trans('calculation.edit.empty');
            $this->Cell(0, 0, $message, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_CENTER);

            return true;
        }

        // items
        CalculationTableItems::render($this);
        $this->Ln(3);

        // check new page
        $this->checkTablesHeight($calculation);

        // totals by group
        CalculationTableGroups::render($this);

        // overall totals
        CalculationTableOverall::render($this);

        return true;
    }

    /**
     * Checks if the groups table and the overall table fit within the current page.
     */
    private function checkTablesHeight(Calculation $calculation): void
    {
        // groups header + groups count + groups footer
        $lines = $calculation->getGroupsCount() + 2;

        // net total + user margin
        if (!empty($calculation->getUserMargin())) {
            $lines += 2;
        }

        // overall margin + overall total + time stampable
        $lines += 3;

        // check
        if (!$this->isPrintable(2 + self::LINE_HEIGHT * $lines)) {
            $this->AddPage();
        }
    }

    /**
     * Render the calculation properties.
     */
    private function renderCalculation(): void
    {
        $calculation = $this->calculation;

        $columns = [
            PdfColumn::left(null, 100),
            PdfColumn::right(null, 40, true),
        ];

        $state = $calculation->getStateCode();
        $date = FormatUtils::formatDate($calculation->getDate());
        $style = PdfStyle::getHeaderStyle()->setBorder('tbr');

        $table = new PdfTableBuilder($this);
        $table->setHeaderStyle(PdfStyle::getHeaderStyle()->setBorder('tbl'));
        $table->addColumns($columns)
            ->startHeaderRow()
            ->add($calculation->getCustomer())
            ->add($state, 1, $style)
            ->endRow()

            ->startHeaderRow()
            ->add($calculation->getDescription())
            ->add($date, 1, $style)
            ->endRow();
    }
}
