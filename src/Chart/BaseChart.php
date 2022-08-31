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

namespace App\Chart;

use App\Service\ApplicationService;
use App\Traits\MathTrait;
use App\Traits\TranslatorAwareTrait;
use App\Util\DateUtils;
use App\Util\FormatUtils;
use Laminas\Json\Expr;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * High chart with method shortcuts.
 *
 * @method BaseChart style(array $styles) set the CSS style.
 * @method BaseChart xAxis(array $xAxis)  set the x-axis.
 * @method BaseChart yAxis(array $yAxis)  set the y-axis.
 *
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $xAxis       the x-axis.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $yAxis       the y-axis.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $chart       the chart.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $credits     the credits.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $legend      the legend.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $tooltip     the tooltip.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $plotOptions the plot options.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $lang        the language.
 * @property \Ob\HighchartsBundle\Highcharts\ChartOption $title       the language.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BaseChart extends Highchart implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The identifier (#id) of the div where to render the chart.
     */
    final public const CONTAINER = 'chartContainer';

    /**
     * The column chart type.
     */
    final public const TYPE_COLUMN = 'column';

    /**
     * The line chart type.
     */
    final public const TYPE_LINE = 'line';

    /**
     * The pie chart type.
     */
    final public const TYPE_PIE = 'pie';

    /**
     * The spline chart type.
     */
    final public const TYPE_SP_LINE = 'spline';

    /**
     * Constructor.
     */
    public function __construct(protected ApplicationService $application)
    {
        parent::__construct();

        $this->hideCredits()
            ->initLangOptions()
            ->setRenderTo(self::CONTAINER)
            ->setBackground('transparent')
            ->setFontFamily('var(--font-family-sans-serif)');
    }

    /**
     * Add a chart event handler.
     *
     * @param string $eventName the chart event name
     * @param Expr   $handler   the event handler
     */
    public function addChartEventListener(string $eventName, Expr $handler): static
    {
        /** @psalm-var \stdClass $events */
        $events = $this->chart->events ?? new \stdClass();
        $events->$eventName = $handler;
        // @phpstan-ignore-next-line
        $this->chart->events($events);

        return $this;
    }

    /**
     * Hides the credits text.
     *
     * @psalm-suppress MixedMethodCall
     */
    public function hideCredits(): static
    {
        // @phpstan-ignore-next-line
        $this->credits->enabled(false);

        return $this;
    }

    /**
     * Hides the series legend.
     */
    public function hideLegend(): static
    {
        // @phpstan-ignore-next-line
        $this->legend->enabled(false);

        return $this;
    }

    /**
     * Hides the chart title.
     */
    public function hideTitle(): static
    {
        return $this->setTitle(null);
    }

    /**
     * Sets background color for the outer chart area.
     */
    public function setBackground(string $color): static
    {
        // @phpstan-ignore-next-line
        $this->chart->backgroundColor($color);

        return $this;
    }

    /**
     * Sets the font family.
     */
    public function setFontFamily(string $font): static
    {
        $this->style(['fontFamily' => $font]);

        return $this;
    }

    /**
     * Sets the HTML element where the chart will be rendered.
     */
    public function setRenderTo(string $id): static
    {
        // @phpstan-ignore-next-line
        $this->chart->renderTo($id);

        return $this;
    }

    /**
     * Sets the chart title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setTitle(?string $title): static
    {
        // @phpstan-ignore-next-line
        $this->title->text($title);

        return $this;
    }

    /**
     * Sets the chart type.
     *
     * @param string $type the chart type to set
     * @psalm-param 'column'|'line'|'pie'|'spline' $type
     */
    public function setType(string $type): static
    {
        // @phpstan-ignore-next-line
        $this->chart->type($type);

        return $this;
    }

    /**
     * Sets the x-axis categories.
     *
     * @param mixed $categories the categories to set
     */
    public function setXAxisCategories(mixed $categories): self
    {
        // @phpstan-ignore-next-line
        $this->xAxis->categories($categories);

        return $this;
    }

    /**
     * Sets the x-axis title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setXAxisTitle(?string $title): self
    {
        // @phpstan-ignore-next-line
        $this->xAxis->title(['text' => $title]);

        return $this;
    }

    /**
     * Sets the y-axis title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setYAxisTitle(?string $title): self
    {
        // @phpstan-ignore-next-line
        $this->yAxis->title(['text' => $title]);

        return $this;
    }

    /**
     * Create an expression.
     *
     * @param string $expression the expression to represent
     *
     * @return Expr the expression
     */
    protected function createExpression(string $expression): Expr
    {
        return new Expr($expression);
    }

    /**
     * Gets the default font style.
     *
     * @return string[]
     */
    protected function getFontStyle(int $fontSize = 16): array
    {
        return [
            'fontWeight' => 'normal',
            'fontSize' => $fontSize . 'px',
            'fontFamily' => '"Lucida Grande", "Lucida Sans Unicode", Arial, Helvetica, sans-serif',
        ];
    }

    /**
     * Translate the given key within the 'chart' domain.
     *
     * @param string $id         the message id (may also be an object that can be cast to string)
     * @param array  $parameters an array of parameters for the message
     *
     * @return string the translated string or the message id if this translator is not defined
     */
    protected function transChart(string $id, array $parameters = []): string
    {
        return $this->trans($id, $parameters, 'chart');
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
