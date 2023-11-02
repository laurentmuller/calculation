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
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use HighchartsBundle\Highcharts\ChartOption;
use HighchartsBundle\Highcharts\Highchart;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Extends the Highchart with method shortcuts.
 */
class AbstractHighchart extends Highchart implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The default identifier (#id) of the div where to render the chart.
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
    public function __construct(protected readonly ApplicationService $application)
    {
        parent::__construct();
        $this->setRenderTo()
            ->hideCredits()
            ->hideAccessibility()
            ->initFontStyle()
            ->initLangOptions()
            ->setBackground('var(--bs-body-bg)');
    }

    /**
     * Disable the accessibility.
     */
    public function hideAccessibility(): static
    {
        $this->accessibility['enabled'] = false;

        return $this;
    }

    /**
     * Disable the credits text.
     */
    public function hideCredits(): static
    {
        $this->credits['enabled'] = false;

        return $this;
    }

    /**
     * Disable the series legend.
     */
    public function hideLegend(): static
    {
        $this->legend['enabled'] = false;

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
     * Initialize the chart font style.
     */
    public function initFontStyle(): static
    {
        $this->chart['style'] = $this->getFontStyle();

        return $this;
    }

    /**
     * Sets the chart background color.
     */
    public function setBackground(string $color): static
    {
        $this->chart['backgroundColor'] = $color;

        return $this;
    }

    /**
     * Sets the HTML element where the chart will be rendered.
     */
    public function setRenderTo(string $id = self::CONTAINER): static
    {
        $this->chart['renderTo'] = $id;

        return $this;
    }

    /**
     * Sets the series.
     */
    public function setSeries(array $series): static
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Sets the chart title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setTitle(?string $title): static
    {
        $this->title['text'] = $title;

        return $this;
    }

    /**
     * Sets the chart type.
     *
     * @psalm-param self::TYPE_* $type
     */
    public function setType(string $type): static
    {
        $this->chart['type'] = $type;

        return $this;
    }

    /**
     * Sets the x-axis.
     */
    public function setXAxis(ChartOption|array $xAxis): static
    {
        $this->xAxis = $xAxis;

        return $this;
    }

    /**
     * Sets the x-axis categories.
     */
    public function setXAxisCategories(mixed $categories): self
    {
        $this->xAxis['categories'] = $categories;

        return $this;
    }

    /**
     * Sets the x-axis title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setXAxisTitle(?string $title): self
    {
        $this->xAxis['title'] = ['text' => $title];

        return $this;
    }

    /**
     * Sets the y-axis.
     */
    public function setYAxis(ChartOption|array $yAxis): static
    {
        $this->yAxis = $yAxis;

        return $this;
    }

    /**
     * Sets the y-axis title.
     *
     * @param ?string $title the title to set or null to hide
     */
    public function setYAxisTitle(?string $title): self
    {
        $this->yAxis['title'] = ['text' => $title];

        return $this;
    }

    /**
     * Gets the border color.
     */
    protected function getBorderColor(): string
    {
        return 'var(--bs-border-color)';
    }

    /**
     * Gets the font style.
     */
    protected function getFontStyle(int $fontSize = 16): array
    {
        return [
            'fontWeight' => 'normal',
            'fontSize' => "{$fontSize}px",
            'fontFamily' => 'var(--bs-body-font-family)',
        ];
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    protected function getMinMargin(): float
    {
        return $this->application->getMinMargin();
    }

    /**
     * Sets the tooltip options.
     */
    protected function setTooltipOptions(): static
    {
        $this->tooltip->merge([
            'borderRadius' => 4,
            'style' => $this->getFontStyle(12),
            'backgroundColor' => 'var(--bs-light)',
            'borderColor' => $this->getBorderColor(),
        ]);

        return $this;
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
    private function initLangOptions(): static
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
            $this->lang[$id] = $value;
        }

        return $this;
    }
}
