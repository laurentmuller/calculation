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
use App\Entity\Log;
use App\Model\LogFile;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Events\PdfCellBorderEvent;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\PdfBorder;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfFont;
use App\Pdf\PdfLine;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Psr\Log\LogLevel;

/**
 * Report for the log.
 */
class LogsReport extends AbstractReport implements PdfDrawCellBorderInterface
{
    /**
     * The borderline width.
     */
    private const FULL_WIDTH = 0.5;

    /**
     * The half borderline width.
     */
    private const HALF_WIDTH = 0.25;

    /**
     * The border colors.
     *
     * @var array<string, ?PdfDrawColor>
     */
    private array $colors = [];

    /**
     * The draw cards state.
     */
    private bool $drawCards = false;

    /**
     * The current level.
     */
    private ?string $level = null;

    /**
     * The total text.
     */
    private string $total;

    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller, PdfDocumentOrientation::LANDSCAPE);
        $this->setTitleTrans('log.title');
        $description = $this->trans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
        $this->getHeader()->setDescription($description);
        $this->total = $this->trans('report.total');
    }

    public function drawCellBorder(PdfCellBorderEvent $event): bool
    {
        if ($this->drawCards) {
            $columns = $event->table->getColumns();
            $text = $columns[$event->index]->getText();

            return $this->drawBorder($event, $text);
        }

        return (0 === $event->index) && $this->drawBorder($event, $this->level);
    }

    /**
     * @throws PdfException
     */
    public function render(): bool
    {
        $this->AddPage();
        $logFile = $this->logFile;
        if ($logFile->isEmpty()) {
            $this->Cell(txt: $this->trans('log.list.empty'));

            return true;
        }
        $this->outputCards();

        return $this->outputLogs($logFile->getLogs());
    }

    /**
     * @throws PdfException
     */
    private function addDateBookmark(int $date): void
    {
        $start_text = FormatUtils::formatDateTime($date + 3600, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $end_text = FormatUtils::formatTime($date, \IntlDateFormatter::SHORT);
        $this->addBookmark($start_text . ' - ' . $end_text);
    }

    private function cellTitle(): void
    {
        PdfFont::default()->bold()->apply($this);
        $this->Cell(txt: $this->trans('log.name'), ln: PdfMove::BELOW);
        $this->resetStyle();
    }

    private function drawBorder(PdfCellBorderEvent $event, ?string $level): bool
    {
        if (null === $level || '' === $level) {
            return false;
        }

        $color = $this->getLevelColor($level);
        if (!$color instanceof PdfDrawColor) {
            return false;
        }

        $bounds = $event->bounds;
        $x = $bounds->x() + self::HALF_WIDTH;
        $y = $bounds->y() + self::HALF_WIDTH;
        $h = $bounds->height() - self::FULL_WIDTH;

        $parent = $event->getDocument();
        $parent->rectangle($bounds, $event->border);
        $color->apply($parent);
        $parent->SetLineWidth(self::FULL_WIDTH);
        $parent->Line($x, $y, $x, $y + $h);
        PdfLine::default()->apply($parent);
        PdfDrawColor::cellBorder()->apply($parent);

        return true;
    }

    private function getLevelColor(string $level): ?PdfDrawColor
    {
        if (!\array_key_exists($level, $this->colors)) {
            $this->colors[$level] = match ($level) {
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::EMERGENCY,
                LogLevel::ERROR => HtmlBootstrapColor::DANGER->getDrawColor(),
                LogLevel::WARNING => HtmlBootstrapColor::WARNING->getDrawColor(),
                LogLevel::DEBUG => HtmlBootstrapColor::SECONDARY->getDrawColor(),
                LogLevel::INFO,
                LogLevel::NOTICE => HtmlBootstrapColor::INFO->getDrawColor(),
                default => null
            };
        }

        return $this->colors[$level];
    }

    private function getShortDate(Log $log): int
    {
        $timestamp = $log->getTimestamp();
        $timestamp -= ($timestamp % 3600);

        return $timestamp - $timestamp % 60;
    }

    private function outputCards(): void
    {
        $levels = $this->logFile->getLevels();
        $channels = $this->logFile->getChannels();

        $columns = [];
        $textCells = [];
        $valueCells = [];
        $sepCol = PdfColumn::center(null, 3);
        $emptyCol = PdfColumn::center(null, 1);
        $emptyCell = new PdfCell(style: PdfStyle::getNoBorderStyle());

        $this->updateCardsEntries($levels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        $this->updateCardsEntries($channels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        $columns[] = PdfColumn::center($this->total, 30);
        $textCells[] = new PdfCell($this->total);
        $valueCells[] = new PdfCell(FormatUtils::formatInt($this->logFile->count()));

        $this->drawCards = true;
        /** @psalm-var positive-int $levelsCount */
        $levelsCount = \count($levels) * 2;
        $channelsCount = \count($channels) * 2 + 1;
        $titleStyle = PdfStyle::default()->setBorder(PdfBorder::NONE)->setFontBold();
        PdfTable::instance($this)
            ->addColumns(...$columns)
            ->startRow()
            ->add($this->trans('log.fields.level'), $levelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->add($this->trans('log.fields.channel'), $channelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->endRow()
            ->setBorderListener($this)
            ->addStyledRow($textCells, PdfStyle::getHeaderStyle()->resetFont())
            ->addStyledRow($valueCells, PdfStyle::getCellStyle()->setFontSize(14))
            ->setBorderListener(null);
        $this->drawCards = false;
        $this->Ln(3);
    }

    /**
     * @psalm-param Log[] $logs the logs
     *
     * @throws PdfException
     */
    private function outputLogs(array $logs): bool
    {
        $date = 0;
        $this->cellTitle();
        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('log.fields.level'), 20, true),
                PdfColumn::left($this->trans('log.fields.channel'), 20, true),
                PdfColumn::left($this->trans('log.fields.createdAt'), 34, true),
                PdfColumn::left($this->trans('log.fields.message'), 150),
                PdfColumn::left($this->trans('log.fields.user'), 20, true)
            )
            ->outputHeaders()
            ->setBorderListener($this);

        foreach ($logs as $log) {
            $this->level = $log->getLevel();
            $newDate = $this->getShortDate($log);
            if ($date !== $newDate) {
                $date = $newDate;
                $this->addDateBookmark($date);
            }
            $table->addRow(
                $log->getLevel(true),
                $log->getChannel(true),
                $log->getFormattedDate(),
                $log->getMessage(),
                $log->getUser()
            );
        }
        $table->setBorderListener(null);

        return $this->renderCount($table, $logs, 'counters.logs');
    }

    /**
     * @psalm-param array<string, int> $values
     * @psalm-param PdfColumn[]        $columns
     * @psalm-param PdfCell[]          $textCells
     * @psalm-param PdfCell[]          $valueCells
     */
    private function updateCardsEntries(
        array $values,
        array &$columns,
        array &$textCells,
        array &$valueCells,
        PdfColumn $emptyCol,
        PdfCell $emptyCell
    ): void {
        $index = \count($values) - 1;
        foreach ($values as $key => $value) {
            $columns[] = PdfColumn::center($key, 30);
            $textCells[] = new PdfCell(StringUtils::capitalize($key));
            $valueCells[] = new PdfCell(FormatUtils::formatInt($value));
            if ($index-- > 0) {
                $columns[] = $emptyCol;
                $textCells[] = $emptyCell;
                $valueCells[] = $emptyCell;
            }
        }
    }
}
