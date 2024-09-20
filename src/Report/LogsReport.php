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
use App\Model\FontAwesomeImage;
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeService;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;

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
     * @var array<string, ?string>
     */
    private array $colors = [];

    public function __construct(
        AbstractController $controller,
        private readonly LogFile $logFile,
        private readonly FontAwesomeService $service
    ) {
        parent::__construct($controller, PdfOrientation::LANDSCAPE);
        $this->setTitleTrans('log.title');
        $this->setDescriptionTrans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
    }

    public function render(): bool
    {
        $this->addPage();
        $logFile = $this->logFile;
        if ($logFile->isEmpty()) {
            $this->cell(text: $this->trans('log.list.empty'));

            return true;
        }

        $this->renderCards($logFile->getLevels(), $logFile->getChannels(), $logFile->count());
        $this->renderLogs($logFile->getLogs());

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
                $this->leftColumn('log.fields.level', 21, true),
                $this->leftColumn('log.fields.channel', 24, true),
                $this->leftColumn('log.fields.user', 20, true)
            )
            ->outputHeaders();
    }

    private function getChannelCell(Log $log): PdfFontAwesomeCell|string
    {
        $text = $log->getChannel(true);
        $image = $this->service->getImageFromIcon($log->getChannelIcon());
        if (!$image instanceof FontAwesomeImage) {
            return $text;
        }

        return new PdfFontAwesomeCell($image, $text);
    }

    private function getImageIcon(LogLevel|LogChannel $value): ?FontAwesomeImage
    {
        $color = null;
        if ($value instanceof LogLevel) {
            $icon = $value->getLevelIcon();
            $color = $this->getLevelColor($value->getLevel());
        } else {
            $icon = $value->getChannelIcon();
        }

        return $this->service->getImageFromIcon($icon, $color);
    }

    private function getLevelCell(Log $log): PdfFontAwesomeCell|string
    {
        $text = $log->getLevel(true);
        $color = $this->getLevelColor($log->getLevel());
        $image = $this->service->getImageFromIcon($log->getLevelIcon(), $color);
        if (!$image instanceof FontAwesomeImage) {
            return $text;
        }

        return new PdfFontAwesomeCell($image, $text);
    }

    private function getLevelColor(string $level): ?string
    {
        if (!\array_key_exists($level, $this->colors)) {
            $color = LogLevel::instance($level)->getLevelColor();
            $this->colors[$level] = HtmlBootstrapColor::parseTextColor($color)?->asHex('#');
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
    private function renderCards(array $levels, array $channels, int $count): void
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

        $levelsCount = \max(1, \count($levels) * 2);
        $channelsCount = \count($channels) * 2 + 1;
        $titleStyle = PdfStyle::default()
            ->setBorder(PdfBorder::none())
            ->setFontBold();

        PdfTable::instance($this)
            ->addColumns(...$columns)
            ->startRow()
            ->add($level, $levelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->add($chanel, $channelsCount, $titleStyle, PdfTextAlignment::LEFT)
            ->endRow()
            ->addStyledRow($textCells, PdfStyle::getHeaderStyle()->resetFont())
            ->addStyledRow($valueCells, PdfStyle::getCellStyle()->setFontSize(14));
        $this->lineBreak(3);
    }

    /**
     * @psalm-param Log[] $logs
     */
    private function renderLogs(array $logs): void
    {
        $title = $this->trans('log.name');
        $this->addBookmark($title, true);
        PdfFont::default()->bold()->apply($this);
        $this->cell(text: $title, move: PdfMove::BELOW);
        $this->resetStyle();

        $date = 0;
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
                $this->getLevelCell($log),
                $this->getChannelCell($log),
                $log->getUser()
            );
        }

        $this->renderCount($table, $logs, 'counters.logs');
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
            $image = $this->getImageIcon($value);
            if ($image instanceof FontAwesomeImage) {
                $textCells[] = new PdfFontAwesomeCell($image, $text, alignment: PdfTextAlignment::CENTER);
            } else {
                $textCells[] = new PdfCell($text);
            }
            $valueCells[] = new PdfCell(FormatUtils::formatInt($value));
            if (--$index > 0) {
                $columns[] = $emptyCol;
                $textCells[] = $emptyCell;
                $valueCells[] = $emptyCell;
            }
        }
    }
}
