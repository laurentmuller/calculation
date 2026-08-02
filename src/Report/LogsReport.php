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

use App\Entity\Log;
use App\Interfaces\DocumentHelperInterface;
use App\Model\FontAwesomeIcon;
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

    /** The delta date, in seconds, between log bookmarks. */
    private const int DELTA_DATE = 600;

    /** @var array<string, PdfFontAwesomeCell|string> */
    private array $cells = [];

    /** @var array<string, string> */
    private array $colors = [];

    public function __construct(
        DocumentHelperInterface $helper,
        private readonly LogFile $logFile,
        string $relativePath,
        private readonly FontAwesomeService $service
    ) {
        parent::__construct($helper, PdfOrientation::LANDSCAPE);
        $this->setTranslatedTitle('log.title')
            ->setTranslatedDescription('log.list.file', ['%file%' => $relativePath]);
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
        PdfStyle::default()->setFontBold()->updateDocument($this);
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

    private function getCell(string $text, FontAwesomeIcon $icon, ?string $color = null): PdfFontAwesomeCell|string
    {
        $key = \sprintf('%s_%s_%s', $text, $icon->getKey(), $color ?? '');
        if (isset($this->cells[$key])) {
            return $this->cells[$key];
        }
        $cell = $this->service->getFontAwesomeCell(icon: $icon, color: $color, text: $text) ?? $text;

        return $this->cells[$key] = $cell;
    }

    private function getCellChannel(Log $log): PdfFontAwesomeCell|string
    {
        return $this->getCell(
            $log->getChannelTitle(),
            $log->getChannelFontAwesomeIcon()
        );
    }

    private function getCellLevel(Log $log): PdfFontAwesomeCell|string
    {
        return $this->getCell(
            $log->getLevelTitle(),
            $log->getLevelFontAwesomeIcon(),
            $this->getLevelColor($log->getLevel())
        );
    }

    private function getImageIcon(LogLevel|LogChannel $value, string $text): PdfCell
    {
        $color = null;
        if ($value instanceof LogLevel) {
            $icon = $value->getLevelFontAwesomeIcon();
            $color = $this->getLevelColor($value->getLevel());
        } else {
            $icon = $value->getChannelFontAwesomeIcon();
        }

        $alignment = PdfTextAlignment::CENTER;
        $cell = $this->service->getFontAwesomeCell(
            icon: $icon,
            color: $color,
            text: $text,
            alignment: $alignment
        );

        return $cell ?? PdfCell::instance(text: $text, alignment: $alignment);
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
        $color = HtmlBootstrapColor::parseTextColor($levelColor)?->asHex('#') ?? FontAwesomeImageService::BLACK_COLOR;

        return $this->colors[$level] = $color;
    }

    private function getRoundedDate(Log $log): int
    {
        return (int) \ceil($log->getTimestamp() / self::DELTA_DATE) * self::DELTA_DATE;
    }

    private function renderCards(): self
    {
        $columns = [];
        $textCells = [];
        $valueCells = [];
        $sepCol = PdfColumn::center(width: 2, fixed: true);
        $emptyCell = PdfCell::instance(style: PdfStyle::getNoBorderStyle());

        // levels
        $this->updateCardsEntries($this->logFile->getLevels(), $columns, $textCells, $valueCells);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        // channels
        $this->updateCardsEntries($this->logFile->getChannels(), $columns, $textCells, $valueCells);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        // total
        $columns[] = PdfColumn::center(width: 30);
        $textCells[] = PdfCell::instance($this->trans('report.total'));
        $valueCells[] = PdfCell::instance(FormatUtils::formatInt($this->logFile->count()));

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
            ->updateDocument($this);
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
    private function updateCardsEntries(array $values, array &$columns, array &$textCells, array &$valueCells): void
    {
        foreach ($values as $key => $value) {
            $columns[] = PdfColumn::center($key, 30);
            $text = StringUtils::capitalize($key);
            $textCells[] = $this->getImageIcon($value, $text);
            $valueCells[] = PdfCell::instance(FormatUtils::formatInt($value));
        }
    }
}
