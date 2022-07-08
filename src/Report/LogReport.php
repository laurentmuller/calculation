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
use App\Pdf\Enums\PdfMove;
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
use App\Service\LogService;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

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
     * The SQL formatter for doctrine message.
     */
    private ?SqlFormatter $formatter = null;

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
     * @param array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>} $entries
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, private readonly array $entries)
    {
        parent::__construct($controller);
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
            $level = $builder->getColumns()[$index]->getText();

            return $this->drawBorder($builder, $level, $bounds, $border);
        }

        // lines
        return (0 === $index) && $this->drawBorder($builder, $this->level, $bounds, $border);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // title
        $this->setTitleTrans('log.title');

        // description
        $description = $this->trans('log.list.file', [
            '%file%' => $this->entries['file'],
        ]);
        $this->header->setDescription($description);

        // new page
        $this->AddPage();

        // logs?
        $logs = $this->entries['logs'];
        if (empty($logs)) {
            $this->Cell(0, self::LINE_HEIGHT, $this->trans('log.list.empty'));

            return true;
        }

        // channels and levels
        $this->outputCards('log.fields.channel', $this->entries['channels']);
        $this->outputCards('log.fields.level', $this->entries['levels']);

        // logs
        return $this->outputLogs($logs);
    }

    private function capitalize(?string $channel): ?string
    {
        return null !== $channel ? Utils::capitalize($channel) : null;
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

    private function formatDate(?\DateTimeInterface $date): ?string
    {
        return FormatUtils::formatDateTime($date, null, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the message for the given log.
     */
    private function formatMessage(Log $log): string
    {
        $message = (string) $log->getMessage();
        if ('doctrine' === $log->getChannel()) {
            $message = $this->formatSql($message);
        }
        if (!empty($log->getContext())) {
            $message .= "\nContext:\n" . (string) Utils::exportVar((array) $log->getContext());
        }
        if (!empty($log->getExtra())) {
            $message .= "\nExtra:\n" . (string) Utils::exportVar((array) $log->getExtra());
        }

        return $message;
    }

    /**
     * Format the given Sql query.
     */
    private function formatSql(string $sql): string
    {
        if (null === $this->formatter) {
            $this->formatter = new SqlFormatter(new NullHighlighter());
        }

        return $this->formatter->format($sql);
    }

    /**
     * Gets the border color for the given level.
     */
    private function getColor(string $level): ?PdfDrawColor
    {
        if (!\array_key_exists($level, $this->colors)) {
            $this->colors[$level] = match ($level) {
                'warning' => PdfDrawColor::create('#ffc107'),
                'error', 'critical', 'alert', 'emergency' => PdfDrawColor::create('#dc3545'),
                'debug' => PdfDrawColor::create('#007bff'),
                'info', 'notice' => PdfDrawColor::create('#17a2b8'),
                default => null,
            };
        }

        return $this->colors[$level];
    }

    /**
     * Output header cards.
     *
     * @param array<string, int> $cards the cards to output
     */
    private function outputCards(string $title, array $cards): void
    {
        // title
        $this->cellTitle($title);

        // total
        $cards['total'] = \array_sum($cards);

        $this->started = true;
        $this->drawCards = true;

        $columns = [];
        $valCells = [];
        $textCells = [];

        $emptyCol = PdfColumn::center(null, 1);
        $emptyCell = new PdfCell(null, 1, PdfStyle::getNoBorderStyle());

        // build columns and cells
        $index = \count($cards) - 1;
        foreach ($cards as $key => $value) {
            $columns[] = PdfColumn::center($key, 25);
            $valCells[] = new PdfCell(FormatUtils::formatInt($value));
            $textCells[] = new PdfCell($this->capitalize($key));

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
        $table->addColumns(...$columns)
            ->row($valCells, PdfStyle::getCellStyle()->setFontSize(18))
            ->row($textCells, PdfStyle::getHeaderStyle()->resetFont());

        $this->Ln(3);
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

        // sort
        LogService::sortLogs($logs);

        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumns(
                PdfColumn::left($this->trans('log.fields.createdAt'), 45),
                PdfColumn::left($this->trans('log.fields.channel'), 30),
                PdfColumn::left($this->trans('log.fields.level'), 30),
                PdfColumn::left($this->trans('log.fields.message'), 150)
            )->outputHeaders();

        foreach ($logs as $log) {
            $this->level = $log->getLevel();
            $table->addRow(
                $this->formatDate($log->getCreatedAt()),
                $this->capitalize($log->getChannel()),
                $this->capitalize($log->getLevel()),
                $this->formatMessage($log)
            );
        }

        return $this->renderCount($logs);
    }
}
