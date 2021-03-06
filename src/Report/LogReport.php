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

use App\Entity\Log;
use App\Pdf\PdfCell;
use App\Pdf\PdfCellListenerInterface;
use App\Pdf\PdfCellListenerTrait;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfLine;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

/**
 * Report for the log.
 *
 * @author Laurent Muller
 */
class LogReport extends AbstractArrayReport implements PdfCellListenerInterface
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
     * @var ?PdfDrawColor[]
     */
    private $colors;

    /**
     * The draw cards state.
     *
     * @var bool
     */
    private $drawCards;

    /**
     * The SQL formatter for doctrine message.
     *
     * @var SqlFormatter
     */
    private $formatter;

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
        return (0 === $index) && $this->drawBorder($builder, $this->level, $bounds, $border);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('log.title');

        // file
        $file = $this->trans('log.list.file', [
            '%file%' => $entities['file'],
        ]);
        $this->setDescription($file);

        // new page
        $this->AddPage();

        // logs
        $logs = $entities['logs'];
        if (empty($logs)) {
            $this->Cell(0, self::LINE_HEIGHT, $this->trans('log.list.empty'));

            return true;
        }

        // levels and channels
        $cards = \array_merge($entities['levels'], $entities['channels']);
        $this->outputCards($cards);

        // lines
        return $this->outputLogs($logs);
    }

    /**
     * Draws the left border if applicable.
     *
     * @param string|int $border the border style
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
     * Format the given Sql query.
     *
     * @param string $sql the query to format
     *
     * @return string the formatted query
     */
    private function formatSql(string $sql): string
    {
        if (null === $this->formatter) {
            $this->formatter = new SqlFormatter(new NullHighlighter());
        }

        return $this->formatter->format($sql);
    }

    /**
     * Gets the border draw color for the given level.
     *
     * @param string $level the level
     *
     * @return PdfDrawColor|null the color or null if none
     */
    private function getColor(string $level): ?PdfDrawColor
    {
        if (null === $this->colors || !\array_key_exists($level, $this->colors)) {
            switch ($level) {
                case 'warning':
                    $this->colors[$level] = PdfDrawColor::create('#ffc107');
                    break;
                case 'error':
                case 'critical':
                case 'alert':
                case 'emergency':
                    $this->colors[$level] = PdfDrawColor::create('#dc3545');
                    break;
                case 'debug':
                    $this->colors[$level] = PdfDrawColor::create('#007bff');
                    break;
                case 'info':
                case 'notice':
                    $this->colors[$level] = PdfDrawColor::create('#17a2b8');
                    break;
                default:
                    $this->colors[$level] = null;
                    break;
            }
        }

        /** @var ?PdfDrawColor $color */
        $color = $this->colors[$level];

        return $color;
    }

    /**
     * Gets the message for the given log.
     */
    private function getMessage(Log $log): string
    {
        if ('doctrine' === $log->getChannel()) {
            $message = $this->formatSql($log->getMessage());
        } else {
            $message = $log->getMessage();
        }
        if (!empty($log->getContext())) {
            $message .= "\n" . Utils::exportVar($log->getContext());
        }
        if (!empty($log->getExtra())) {
            $message .= "\n" . Utils::exportVar($log->getExtra());
        }

        return $message;
    }

    /**
     * Output header cards.
     *
     * @param array $cards the cards to output
     */
    private function outputCards(array $cards): void
    {
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
     * Output logs.
     *
     * @param Log[] $logs the logs
     *
     * @return bool true on success
     */
    private function outputLogs(array $logs): bool
    {
        $this->drawCards = false;

        $table = new PdfTableBuilder($this);
        $table->setListener($this)
            ->addColumn(PdfColumn::left($this->trans('log.fields.createdAt'), 45))
            ->addColumn(PdfColumn::left($this->trans('log.fields.level'), 30))
            ->addColumn(PdfColumn::left($this->trans('log.fields.channel'), 30))
            ->addColumn(PdfColumn::left($this->trans('log.fields.message'), 150))
            ->outputHeaders();

        foreach ($logs as $log) {
            $this->level = $log->getLevel();
            $table->startRow()
                ->add(FormatUtils::formatDateTime($log->getCreatedAt(), null, \IntlDateFormatter::MEDIUM))
                ->add(Utils::capitalize($log->getLevel()))
                ->add(Utils::capitalize($log->getChannel()))
                ->add($this->getMessage($log))
                ->endRow();
        }

        return $this->renderCount($logs);
    }
}
