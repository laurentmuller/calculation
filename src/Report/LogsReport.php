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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeImageService;
use App\Service\FontAwesomeService;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use Psr\Log\LogLevel as PsrLevel;

/**
 * Report for the log.
 */
class LogsReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    /**
     * The delta date, in seconds, between log bookmarks.
     */
    private const DELTA_DATE = 600;

    /**
     * @var array<string, PdfFontAwesomeCell|string>
     */
    private array $cells = [];

    /**
     * @var array<string, string>
     */
    private array $colors = [];

    public function __construct(
        AbstractController $controller,
        private readonly LogFile $logFile,
        private readonly FontAwesomeService $service
    ) {
        parent::__construct($controller, PdfOrientation::LANDSCAPE);
        $this->setTranslatedTitle('log.title')
            ->setTranslatedDescription('log.list.file', ['%file%' => $this->logFile->getFile()]);
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        if ($this->logFile->isEmpty()) {
            return $this->renderEmpty();
        }

        return $this->renderCards()
            ->renderLogs();
    }

    private function addBookmarkAndTitle(string $id, bool $currentY): void
    {
        $text = $this->trans($id);
        $this->addBookmark(text: $text, isUTF8: true, currentY: $currentY);
        PdfStyle::default()->setFontBold()->apply($this);
        $this->useCellMargin(fn (): static => $this->cell(text: $text, move: PdfMove::NEW_LINE));
        $this->resetStyle();
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
                $this->leftColumn('log.fields.level', 21, true),
                $this->leftColumn('log.fields.channel', 24, true),
                $this->leftColumn('log.fields.user', 20, true)
            )
            ->outputHeaders();
    }

    private function getCell(
        string $text,
        string $icon,
        string $color = FontAwesomeImageService::COLOR_BLACK
    ): PdfFontAwesomeCell|string {
        $key = \sprintf('%s_%s_%s', $text, $icon, $color);
        if (isset($this->cells[$key])) {
            return $this->cells[$key];
        }
        $cell = $this->service->getFontAwesomeCell($icon, $color, $text) ?? $text;

        return $this->cells[$key] = $cell;
    }

    private function getCellChannel(Log $log): PdfFontAwesomeCell|string
    {
        $text = $log->getChannelTitle();
        $icon = $log->getChannelIcon();

        return $this->getCell($text, $icon);
    }

    private function getCellLevel(Log $log): PdfFontAwesomeCell|string
    {
        $text = $log->getLevelTitle();
        $icon = $log->getLevelIcon();
        $color = $this->getLevelColor($log->getLevel());

        return $this->getCell($text, $icon, $color);
    }

    private function getImageIcon(LogLevel|LogChannel $value, string $text): PdfCell
    {
        $color = null;
        if ($value instanceof LogLevel) {
            $icon = $value->getLevelIcon();
            $color = $this->getLevelColor($value->getLevel());
        } else {
            $icon = $value->getChannelIcon();
        }

        $cell = $this->service->getFontAwesomeCell(
            icon: $icon,
            color: $color,
            text: $text,
            alignment: PdfTextAlignment::CENTER
        );

        return $cell ?? new PdfCell($text);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    private function getLevelColor(string $level): string
    {
        if (\array_key_exists($level, $this->colors)) {
            return $this->colors[$level];
        }

        $levelColor = LogLevel::instance($level)->getLevelColor();
        $color = HtmlBootstrapColor::parseTextColor($levelColor)?->asHex('#') ?? FontAwesomeImageService::COLOR_BLACK;

        return $this->colors[$level] = $color;
    }

    private function getRoundedDate(Log $log): int
    {
        return (int) \ceil($log->getTimestamp() / self::DELTA_DATE) * self::DELTA_DATE;
    }

    private function renderCards(): self
    {
        $levels = $this->logFile->getLevels();
        $channels = $this->logFile->getChannels();

        $columns = [];
        $textCells = [];
        $valueCells = [];
        $sepCol = PdfColumn::center(width: 3);
        $emptyCol = PdfColumn::center(width: 1);
        $emptyCell = new PdfCell(style: PdfStyle::getNoBorderStyle());

        $this->updateCardsEntries($levels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        $this->updateCardsEntries($channels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        $columns[] = PdfColumn::center(width: 30);
        $textCells[] = new PdfCell($this->trans('report.total'));
        $valueCells[] = new PdfCell(FormatUtils::formatInt($this->logFile->count()));

        $this->addBookmarkAndTitle('calculation.edit.panel_resume', false);
        PdfTable::instance($this)
            ->addColumns(...$columns)
            ->addStyledRow($textCells, PdfStyle::getHeaderStyle()->resetFont())
            ->addStyledRow($valueCells, PdfStyle::getCellStyle()->setFontSize(14));
        $this->lineBreak(3);

        return $this;
    }

    private function renderEmpty(): true
    {
        PdfStyle::getHeaderStyle()
            ->setTextColor(PdfTextColor::red())
            ->apply($this);
        $this->cell(
            height: self::LINE_HEIGHT * 1.25,
            text: $this->trans('log.list.empty'),
            border: PdfBorder::all(),
            move: PdfMove::NEW_LINE,
            align: PdfTextAlignment::CENTER,
            fill: true,
        );

        return true;
    }

    private function renderLogs(): bool
    {
        $date = 0;
        $logs = $this->logFile->getLogs();
        $this->addBookmarkAndTitle('log.name', true);
        $table = $this->createTable();
        foreach ($logs as $log) {
            $newDate = $this->getRoundedDate($log);
            if ($date !== $newDate) {
                $date = $newDate;
                $this->addDateBookmark($date);
            }
            $table->addRow(
                $log->getFormattedDate(),
                $log->getMessage(),
                $this->getCellLevel($log),
                $this->getCellChannel($log),
                $log->getUser()
            );
        }

        return $this->renderCount($table, $logs, 'counters.logs');
    }

    /**
     * @param array<string, LogLevel|LogChannel> $values
     * @param PdfColumn[]                        $columns
     * @param PdfCell[]                          $textCells
     * @param PdfCell[]                          $valueCells
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
            $text = StringUtils::capitalize($key);
            $textCells[] = $this->getImageIcon($value, $text);
            $valueCells[] = new PdfCell(FormatUtils::formatInt($value));
            if (--$index > 0) {
                $columns[] = $emptyCol;
                $textCells[] = $emptyCell;
                $valueCells[] = $emptyCell;
            }
        }
    }
}
