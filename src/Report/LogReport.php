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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Html\HtmlBootstrapColors;
use App\Pdf\PdfBorder;
use App\Pdf\PdfCell;
use App\Pdf\PdfCellListenerInterface;
use App\Pdf\PdfCellListenerTrait;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfFont;
use App\Pdf\PdfLine;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use App\Util\Utils;
use Psr\Log\LogLevel;

/**
 * Report for the log.
 */
class LogReport extends AbstractReport implements PdfCellListenerInterface
{
    use PdfCellListenerTrait;

    /**
     * The borderline width.
     */
    private const FULL_WIDTH = 0.5;

    /**
     * The half borderline width.
     */
    private const HALF_WIDTH = 0.25;

    /*
     * The total card text.
     */
    private const TOTAL = 'total';

    /**
     * The border colors.
     *
     * @var array<PdfDrawColor|null>
     */
    private array $colors = [];

    /**
     * The draw cards state.
     */
    private ?bool $drawCards = null;

    /**
     * The current level.
     */
    private ?string $level = null;

    /**
     * The started page state.
     */
    private ?bool $started = null;

    /**
     * Constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller, PdfDocumentOrientation::LANDSCAPE);

        // title and description
        $this->setTitleTrans('log.title');
        $description = $this->trans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
        $this->header->setDescription($description);
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
    public function drawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, PdfBorder $border): bool
    {
        // started?
        if (!$this->started) {
            $this->started = true;

            return false;
        }

        // cards
        if ($this->drawCards) {
            $text = $builder->getColumns()[$index]->getText();
            if (self::TOTAL === $text) {
                return false;
            }

            return $this->drawBorder($builder, $text, $bounds, $border);
        }

        // lines
        return (0 === $index) && $this->drawBorder($builder, $this->level, $bounds, $border);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $logFile = $this->logFile;

        // new page
        $this->AddPage();

        // logs?
        if ($logFile->isEmpty()) {
            $this->Cell(0, self::LINE_HEIGHT, $this->trans('log.list.empty'));

            return true;
        }

        // levels and channels
        $this->outputCards();

        // logs
        return $this->outputLogs($logFile->getLogs());
    }

    private function cellTitle(string $title): void
    {
        PdfFont::default()->bold()->apply($this);
        $this->Cell(0, self::LINE_HEIGHT, $this->trans($title), 0, PdfMove::BELOW);
        $this->resetStyle();
    }

    /**
     * Draws the left border if applicable.
     */
    private function drawBorder(PdfTableBuilder $builder, ?string $level, PdfRectangle $bounds, PdfBorder $border): bool
    {
        if ($level && $color = $this->getLevelColor($level)) {
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
     * Gets the border color for the given level.
     */
    private function getLevelColor(string $level): ?PdfDrawColor
    {
        if (!\array_key_exists($level, $this->colors)) {
            return $this->colors[$level] = match ($level) {
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::EMERGENCY,
                LogLevel::ERROR => PdfDrawColor::create(HtmlBootstrapColors::DANGER),
                LogLevel::WARNING => PdfDrawColor::create(HtmlBootstrapColors::WARNING),
                LogLevel::DEBUG => PdfDrawColor::create(HtmlBootstrapColors::SECONDARY),
                LogLevel::INFO => PdfDrawColor::create(HtmlBootstrapColors::INFO),
                default => null
            };
        }

        return $this->colors[$level];
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
        $emptyCell = new PdfCell(null, 1, PdfStyle::getNoBorderStyle());

        // levels
        $this->outputCardsEntries($levels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        // channels
        $this->outputCardsEntries($channels, $columns, $textCells, $valueCells, $emptyCol, $emptyCell);
        $columns[] = $sepCol;
        $textCells[] = $emptyCell;
        $valueCells[] = $emptyCell;

        // total
        $columns[] = PdfColumn::center(self::TOTAL, 30);
        $textCells[] = new PdfCell(Utils::capitalize(self::TOTAL));
        $valueCells[] = new PdfCell(FormatUtils::formatInt($this->logFile->count()));

        $this->started = true;
        $this->drawCards = true;
        $titleStyle = PdfStyle::getDefaultStyle()->setBorder(PdfBorder::NONE)->setFontBold();

        $table = new PdfTableBuilder($this);
        $table->addColumns(...$columns)
            ->startRow()
            ->add($this->trans('log.fields.level'), \count($levels) * 2, $titleStyle, PdfTextAlignment::LEFT)
            ->add($this->trans('log.fields.channel'), \count($channels) * 2 + 1, $titleStyle, PdfTextAlignment::LEFT)
            ->endRow()
            ->setListener($this)
            ->row($textCells, PdfStyle::getHeaderStyle()->resetFont())
            ->row($valueCells, PdfStyle::getCellStyle()->setFontSize(14));
        $this->Ln(3);
    }

    /**
     * @param array<string, int> $values
     * @param PdfColumn[]        $columns
     * @param PdfCell[]          $textCells
     * @param PdfCell[]          $valueCells
     */
    private function outputCardsEntries(array $values, array &$columns, array &$textCells, array &$valueCells, PdfColumn $emptyCol, PdfCell $emptyCell): void
    {
        $index = \count($values) - 1;
        foreach ($values as $key => $value) {
            $columns[] = PdfColumn::center($key, 30);
            $textCells[] = new PdfCell(Utils::capitalize($key));
            $valueCells[] = new PdfCell(FormatUtils::formatInt($value));
            if ($index-- > 0) {
                $columns[] = $emptyCol;
                $textCells[] = $emptyCell;
                $valueCells[] = $emptyCell;
            }
        }
    }

    /**
     * Output logs.
     *
     * @param Log[] $logs the logs
     *
     * @return bool true on success
     */
    private function outputLogs(array $logs): bool
    {
        $this->drawCards = false;
        $this->cellTitle('log.name');

        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumns(
                PdfColumn::left($this->trans('log.fields.level'), 20, true),
                PdfColumn::left($this->trans('log.fields.channel'), 20, true),
                PdfColumn::left($this->trans('log.fields.createdAt'), 34, true),
                PdfColumn::left($this->trans('log.fields.message'), 150),
                PdfColumn::left($this->trans('log.fields.user'), 20, true)
            )->outputHeaders();

        foreach ($logs as $log) {
            $this->level = $log->getLevel();
            $table->addRow(
                $log->getLevel(true),
                $log->getChannel(true),
                $log->getFormattedDate(),
                $log->getMessage(),
                $log->getUser()
            );
        }

        return $this->renderCount($logs);
    }
}
