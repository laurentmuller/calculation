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
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Events\PdfCellBorderEvent;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;

/**
 * Report for the log.
 */
class LogsReport extends AbstractReport implements PdfDrawCellBorderInterface
{
    /**
     * The borderline width.
     */
    private const BORDER_WIDTH = 0.5;

    /**
     * The delta date, in seconds, between log bookmarks.
     */
    private const DELTA_DATE = 600;

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
     * The maximum index to draw border for card headers.
     */
    private int $levelsCount = 0;

    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller, PdfOrientation::LANDSCAPE);
        $this->setTitleTrans('log.title');
        $this->setDescriptionTrans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
    }

    public function drawCellBorder(PdfCellBorderEvent $event): bool
    {
        // cards
        if ($this->drawCards) {
            if ($event->index >= $this->levelsCount) {
                return false;
            }

            $columns = $event->table->getColumns();
            $text = $columns[$event->index]->getText();

            return $this->drawBorder($event, $text);
        }

        // row
        if (0 === $event->index && !$event->table->isHeaders()) {
            return $this->drawBorder($event, $this->level);
        }

        return false;
    }

    public function render(): bool
    {
        $this->addPage();
        $logFile = $this->logFile;
        if ($logFile->isEmpty()) {
            $this->cell(text: $this->trans('log.list.empty'));

            return true;
        }

        $this->outputCards($logFile->getLevels(), $logFile->getChannels(), $logFile->count());
        $this->outputLogs($logFile->getLogs());

        return true;
    }

    private function addDateBookmark(int $date): void
    {
        $startDate = FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $endDate = FormatUtils::formatTime($date - self::DELTA_DATE, \IntlDateFormatter::SHORT);
        $this->addBookmark(\sprintf('%s - %s', $startDate, $endDate), level: 1);
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('log.fields.createdAt', 33, true),
                $this->leftColumn('log.fields.message', 150),
                $this->leftColumn('log.fields.level', 20, true),
                $this->leftColumn('log.fields.channel', 20, true),
                $this->leftColumn('log.fields.user', 20, true)
            )
            ->outputHeaders();
    }

    private function drawBorder(PdfCellBorderEvent $event, ?string $level): bool
    {
        if (!StringUtils::isString($level)) {
            return false;
        }

        $color = $this->getLevelColor($level);
        if (!$color instanceof PdfDrawColor) {
            return false;
        }

        $bounds = $event->bounds;
        $x = $bounds->x + self::BORDER_WIDTH / 2.0;
        $y = $bounds->y + self::BORDER_WIDTH / 2.0;
        $h = $bounds->height - self::BORDER_WIDTH;
        $parent = $event->getDocument();
        $parent->rectangle($bounds, $event->border);
        $color->apply($parent);
        $parent->setLineWidth(self::BORDER_WIDTH);
        $parent->line($x, $y, $x, $y + $h);

        return true;
    }

    private function getLevelColor(string $level): ?PdfDrawColor
    {
        if (!\array_key_exists($level, $this->colors)) {
            $color = LogLevel::instance($level)->getLevelColor();
            $this->colors[$level] = HtmlBootstrapColor::parseDrawColor($color);
        }

        return $this->colors[$level];
    }

    private function getRoundedDate(Log $log): int
    {
        return (int) \ceil($log->getTimestamp() / self::DELTA_DATE) * self::DELTA_DATE;
    }

    /**
     * @param array<string, LogLevel>   $levels
     * @param array<string, LogChannel> $channels
     */
    private function outputCards(array $levels, array $channels, int $count): void
    {
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

        $total = $this->trans('report.total');
        $columns[] = PdfColumn::center($total, 30);
        $textCells[] = new PdfCell($total);
        $valueCells[] = new PdfCell(FormatUtils::formatInt($count));

        $level = $this->trans('log.fields.level');
        $chanel = $this->trans('log.fields.channel');
        $this->addBookmark(\sprintf('%s - %s', $level, $chanel));

        $this->levelsCount = \max(1, \count($levels) * 2);
        $channelsCount = \count($channels) * 2 + 1;
        $titleStyle = PdfStyle::default()->setBorder(PdfBorder::none())->setFontBold();

        $this->drawCards = true;
        PdfTable::instance($this)
            ->addColumns(...$columns)
            ->startRow()
            ->add($level, $this->levelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->add($chanel, $channelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->endRow()
            ->setBorderListener($this)
            ->addStyledRow($textCells, PdfStyle::getHeaderStyle()->resetFont())
            ->addStyledRow($valueCells, PdfStyle::getCellStyle()->setFontSize(14))
            ->setBorderListener(null);
        $this->drawCards = false;
        $this->lineBreak(3);
    }

    /**
     * @psalm-param Log[] $logs
     */
    private function outputLogs(array $logs): void
    {
        $title = $this->trans('log.name');
        $this->addBookmark($title, true);
        PdfFont::default()->bold()->apply($this);
        $this->cell(text: $title, move: PdfMove::BELOW);
        $this->resetStyle();

        $date = 0;
        $table = $this->createTable()
            ->setBorderListener($this);
        foreach ($logs as $log) {
            $this->level = $log->getLevel();
            $newDate = $this->getRoundedDate($log);
            if ($date !== $newDate) {
                $date = $newDate;
                $this->addDateBookmark($date);
            }
            $table->addRow(
                $log->getFormattedDate(),
                $log->getMessage(),
                $log->getLevel(true),
                $log->getChannel(true),
                $log->getUser()
            );
        }
        $table->setBorderListener(null);
        $this->renderCount($table, $logs, 'counters.logs');
    }

    /**
     * @param array<string, \Countable> $values
     * @param PdfColumn[]               $columns
     * @param PdfCell[]                 $textCells
     * @param PdfCell[]                 $valueCells
     */
    private function updateCardsEntries(
        array $values,
        array &$columns,
        array &$textCells,
        array &$valueCells,
        PdfColumn $emptyCol,
        PdfCell $emptyCell
    ): void {
        $index = \count($values);
        foreach ($values as $key => $value) {
            $columns[] = PdfColumn::center($key, 30);
            $textCells[] = new PdfCell(StringUtils::capitalize($key));
            $valueCells[] = new PdfCell(FormatUtils::formatInt($value));
            if (--$index > 0) {
                $columns[] = $emptyCol;
                $textCells[] = $emptyCell;
                $valueCells[] = $emptyCell;
            }
        }
    }
}
