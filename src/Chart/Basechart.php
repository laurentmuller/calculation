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

namespace App\Chart;

use App\Util\DateUtils;
use App\Util\FormatUtils;
use Ob\HighchartsBundle\Highcharts\Highchart;

/**
 * High chart with method shortcuts.
 *
 * @author Laurent Muller
 *
 * @method Basechart style(array $styles) set the CSS style options.
 * @method Basechart xAxis(array $xAxis)  set the x axis options.
 * @method Basechart yAxis(array $yAxis)  set the y axis options.
 *
 * @property mixed $xAxis the x axis options.
 * @property mixed $yAxis the y axis options.
 */
class Basechart extends Highchart
{
    /**
     * The identifier (#id) of the div where to render the chart.
     */
    public const CONTAINER = 'chartContainer';

    /**
     * The column chart type.
     */
    public const TYPE_COLUMN = 'column';

    /**
     * The line chart type.
     */
    public const TYPE_LINE = 'line';

    /**
     * The pie chart type.
     */
    public const TYPE_PIE = 'pie';

    /**
     * The spline chart type.
     */
    public const TYPE_SP_LINE = 'spline';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->hideCredits();
        $this->chart->renderTo(self::CONTAINER);
        $this->style(['fontFamily' => 'var(--font-family-sans-serif)']);
    }

    /**
     * Hides the credits text.
     */
    public function hideCredits(): self
    {
        $this->credits->enabled(false);

        return $this;
    }

    /**
     * Hides the series legend.
     */
    public function hideLegend(): self
    {
        $this->legend->enabled(false);

        return $this;
    }

    /**
     * Hides the chart title.
     */
    public function hideTitle(): self
    {
        return $this->setTitle(null);
    }

    /**
     * Initialize the language options for this chart.
     */
    public function initLangOptions(): self
    {
        // array options
        $this->setLangOption('months', \array_values(DateUtils::getMonths()))
            ->setLangOption('shortMonths', \array_values(DateUtils::getShortMonths()))
            ->setLangOption('weekdays', \array_values(DateUtils::getWeekdays()))
            ->setLangOption('shortWeekdays', \array_values(DateUtils::getShortWeekdays()));

        // format options
        $this->setLangOption('thousandsSep', FormatUtils::getGrouping())
            ->setLangOption('decimalPoint', FormatUtils::getDecimal());

        return $this;
    }

    /**
     * Sets the chart title.
     *
     * @param string $title the title to set or null to hide
     */
    public function setTitle(?string $title): self
    {
        $this->title->text($title);

        return $this;
    }

    /**
     * Sets the chart type.
     *
     * @param string $type the chart type to set like 'pie' or 'column'. Can be on of this predefined 'TYPE_' constants.
     */
    public function setType(string $type): self
    {
        $this->chart->type($type);

        return $this;
    }

    /**
     * Sets the x axis categories.
     *
     * @param mixed $categories the categories to set
     */
    public function setXAxisCategories($categories): self
    {
        $this->xAxis->categories($categories);

        return $this;
    }

    /**
     * Sets the x axis title.
     *
     * @param string $title the title to set or null to hide
     */
    public function setXAxisTitle(?string $title): self
    {
        $this->xAxis->title([
            'text' => $title,
        ]);

        return $this;
    }

    /**
     * Sets the y axis title.
     *
     * @param string $title the title to set or null to hide
     */
    public function setYAxisTitle(?string $title): self
    {
        $this->yAxis->title([
            'text' => $title,
        ]);

        return $this;
    }

    /**
     * Sets a language option for this chart.
     *
     * @param string $id    the option identifier
     * @param mixed  $value the option value to set
     */
    private function setLangOption($id, $value): self
    {
        $this->lang->{$id}($value);

        return $this;
    }
}
