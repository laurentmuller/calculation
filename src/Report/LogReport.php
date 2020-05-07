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
use App\Pdf\PdfCellListenerInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfCellListenerTrait;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfLine;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Utils\Utils;

/**
 * Report for the log.
 *
 * @author Laurent Muller
 */
class LogReport extends BaseReport implements PdfCellListenerInterface
{
    use PdfCellListenerTrait;

    /**
     * The border line width.
     */
    private const FULL_WIDTH = 0.5;

    /**
     * The half border line width.
     */
    private const HALF_WIDTH = 0.25;

    /**
     * The border colors.
     *
     * @var array
     */
    private $colors;

    /**
     * The draw cards state.
     *
     * @var bool
     */
    private $drawCards;

    /**
     * The current level.
     *
     * @var string
     */
    private $level;

    /**
     * The started page state.
     *
     * @var bool
     */
    private $started;

    /**
     * The values to print.
     *
     * @var array
     */
    private $values;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('logs.title');
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
    public function onDrawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, $border): bool
    {
        // started?
        if (!$this->started) {
            $this->started = true;

            return false;
        }

        // cards
        if ($this->drawCards) {
            $level = $builder->getColumns()[$index]->getText();

            return $this->drawBorder($builder, $level, $bounds, $border);
        }

        // lines
        return  (0 === $index) && $this->drawBorder($builder, $this->level, $bounds, $border);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // values?
        $values = $this->values;
        if (empty($values)) {
            return false;
        }

        // file
        $file = $this->trans('logs.show.file', [
            '%file%' => $values['file'],
        ]);
        $this->setDescription($file);

        // new page
        $this->AddPage();

        // lines
        $lines = $values['lines'];
        if (!$lines) {
            $this->Cell(0, self::LINE_HEIGHT, $this->trans('logs.show.empty'));

            return true;
        }

        // levels and channels
        $cards = \array_merge($this->values['levels'], $this->values['channels']);
        $this->outputCards($cards);

        // lines
        return $this->outputLines($lines);
    }

    /**
     * Sets the values to output.
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Draws the left border if applicable.
     *
     * @param string $level
     * @param mixed  $border
     */
    private function drawBorder(PdfTableBuilder $builder, ?string $level, PdfRectangle $bounds, $border): bool
    {
        if ($level && $color = $this->getColor($level)) {
            // get values
            $x = $bounds->x() + self::HALF_WIDTH;
            $y = $bounds->y() + self::HALF_WIDTH;
            $h = $bounds->height() - self::FULL_WIDTH;
            $doc = $builder->getParent();

            // default
            $doc->rectangle($bounds, $border);

            // left border
            $color->apply($doc);
            $doc->SetLineWidth(self::FULL_WIDTH);
            $doc->Line($x, $y, $x, $y + $h);

            // restore
            PdfLine::default()->apply($doc);
            PdfDrawColor::cellBorder()->apply($doc);

            return true;
        }

        return false;
    }

    /**
     * Gets the border draw color for the given level.
     *
     * @param string $level the level
     *
     * @return PdfDrawColor|null the border draw color or null if none
     */
    private function getColor(string $level): ?PdfDrawColor
    {
        if (!isset($this->colors[$level])) {
            switch ($level) {
                case 'warning':
                    return $this->colors[$level] = PdfDrawColor::create('#ffc107');
                case 'error':
                case 'critical':
                case 'alert':
                case 'emergency':
                    return $this->colors[$level] = PdfDrawColor::create('#dc3545');
                case 'debug':
                    return $this->colors[$level] = PdfDrawColor::create('#007bff');
                case 'info':
                case 'notice':
                    return $this->colors[$level] = PdfDrawColor::create('#17a2b8');
                default:
                    return null;
            }
        }

        return $this->colors[$level];
    }

    /**
     * Gets the message.
     *
     * @param array $line the log line
     *
     * @return string the message
     */
    private function getMessage(array $line): string
    {
        $message = $line['message'];
        if (!empty($line['context'])) {
            $message .= "\n" . Utils::exportVar($line['context']);
        }

        if (!empty($line['extra'])) {
            $message .= "\n" . Utils::exportVar($line['extra']);
        }

        return $message;
    }

    /**
     * Output header cards.
     *
     * @param array $data the cards to output
     */
    private function outputCards(array $data): void
    {
        $this->started = true;
        $this->drawCards = true;

        $columns = [];
        $valCells = [];
        $textCells = [];

        $emptyCol = PdfColumn::center(null, 1);
        $emptyCell = new PdfCell(null, 1, PdfStyle::getNoBorderStyle());

        // build columns and cells
        $index = \count($data) - 1;
        foreach ($data as $key => $value) {
            $columns[] = PdfColumn::center($key, 25);
            $valCells[] = new PdfCell($this->localeInt($value));
            $textCells[] = new PdfCell(Utils::capitalize($key));

            // add separator if not last
            if ($index-- > 0) {
                $columns[] = $emptyCol;
                $valCells[] = $emptyCell;
                $textCells[] = $emptyCell;
            }
        }

        // fill
        $table = new PdfTableBuilder($this);
        $table->setListener($this);
        $table->addColumns($columns)
            ->row($valCells, PdfStyle::getCellStyle()->setFontSize(18))
            ->row($textCells, PdfStyle::getHeaderStyle()->resetFont());

        $this->Ln(3);
    }

    /**
     * Output log lines.
     *
     * @param array $lines the log lines
     *
     * @return bool true on success
     */
    private function outputLines(array $lines): bool
    {
        $this->drawCards = false;

        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumn(PdfColumn::left($this->trans('logs.fields.date'), 45))
            ->addColumn(PdfColumn::left($this->trans('logs.fields.level'), 30))
            ->addColumn(PdfColumn::left($this->trans('logs.fields.channel'), 30))
            ->addColumn(PdfColumn::left($this->trans('logs.fields.message'), 150))
            ->outputHeaders();

        foreach ($lines as $line) {
            $this->level = $line['level'];
            $table->startRow()
                ->add($line['date'])
                ->add(Utils::capitalize($line['level']))
                ->add(Utils::capitalize($line['channel']))
                ->add($this->getMessage($line))
                ->endRow();
        }

        return $this->renderCount($lines);
    }
}
