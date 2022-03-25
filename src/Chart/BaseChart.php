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

use App\Service\ApplicationService;
use App\Service\ThemeService;
use App\Traits\MathTrait;
use App\Traits\TranslatorTrait;
use App\Util\DateUtils;
use App\Util\FormatUtils;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * High chart with method shortcuts.
 *
 * @author Laurent Muller
 *
 * @method BaseChart style(array $styles) set the CSS style options.
 * @method BaseChart xAxis(array $xAxis)  set the x axis options.
 * @method BaseChart yAxis(array $yAxis)  set the y axis options.
 *
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $xAxis       the x axis.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $yAxis       the y axis.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $chart       the chart.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $credits     the credits.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $legend      the legend.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $tooltip     the tooltip.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $plotOptions the plot options.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $lang        the language.
 * @property array                                       $colors      the colors.
 * @property array                                       $series      the series.
 */
class BaseChart extends Highchart
{
    use MathTrait;
    use TranslatorTrait;

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

    protected ApplicationService $application;
    protected ThemeService $service;

    /**
     * Constructor.
     */
    public function __construct(ApplicationService $application, ThemeService $service, TranslatorInterface $translator)
    {
        parent::__construct();
        $this->application = $application;
        $this->service = $service;
        $this->setTranslator($translator);

        $this->hideCredits()
            ->initLangOptions()
            ->setRenderTo(self::CONTAINER)
            ->setBackground('transparent')
            ->setFontFamily('var(--font-family-sans-serif)');
    }

    /**
     * Gets the foreground of the graph label; depending on the theme of the application.
     */
    public function getForeground(): string
    {
        return $this->service->isDarkTheme() ? 'white' : 'black';
    }

    /**
     * Hides the credits text.
     *
     * @psalm-suppress MixedMethodCall
     */
    public function hideCredits(): self
    {
        // @phpstan-ignore-next-line
        $this->credits->enabled(false);

        return $this;
    }

    /**
     * Hides the series legend.
     */
    public function hideLegend(): self
    {
        // @phpstan-ignore-next-line
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
     * Sets background color for the outer chart area.
     */
    public function setBackground(string $color): self
    {
        // @phpstan-ignore-next-line
        $this->chart->backgroundColor('transparent');

        return $this;
    }

    /**
     * Sets the font family.
     */
    public function setFontFamily(string $font): self
    {
        $this->style(['fontFamily' => $font]);

        return $this;
    }

    /**
     * Sets the HTML element where the chart will be rendered.
     */
    public function setRenderTo(string $id): self
    {
        // @phpstan-ignore-next-line
        $this->chart->renderTo($id);

        return $this;
    }

    /**
     * Sets the chart title.
     *
     * @param string $title the title to set or null to hide
     * @psalm-suppress MixedMethodCall
     */
    public function setTitle(?string $title): self
    {
        $this->title->text($title);

        return $this;
    }

    /**
     * Sets the chart type.
     *
     * @param 'column'|'line'|'pie'|'spline' $type the chart type to set
     */
    public function setType(string $type): self
    {
        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
        $this->yAxis->title([
            'text' => $title,
        ]);

        return $this;
    }

    /**
     * Initialize the language options.
     */
    private function initLangOptions(): self
    {
        $options = [
            'thousandsSep' => FormatUtils::getGrouping(),
            'decimalPoint' => FormatUtils::getDecimal(),
            'months' => \array_values(DateUtils::getMonths()),
            'weekdays' => \array_values(DateUtils::getWeekdays()),
            'shortMonths' => \array_values(DateUtils::getShortMonths()),
            'shortWeekdays' => \array_values(DateUtils::getShortWeekdays()),
        ];

        foreach ($options as $id => $value) {
            $this->lang->{$id}($value);
        }

        return $this;
    }
}
