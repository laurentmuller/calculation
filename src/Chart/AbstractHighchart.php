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
use HighchartsBundle\Highcharts\ChartExpression;
use HighchartsBundle\Highcharts\Highchart;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * Extends the Highchart with method shortcuts.
 */
class AbstractHighchart extends Highchart implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The default identifier of the div where to render the chart.
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

    private const COMMENT_REGEX = '/\/\*(.|[\r\n])*?\*\//m';

    public function __construct(
        protected readonly ApplicationService $application,
        protected readonly UrlGeneratorInterface $generator,
        protected readonly Environment $twig
    ) {
        parent::__construct();
        $this->initializeOptions();
    }

    /**
     * Hides the chart title.
     */
    public function hideTitle(): static
    {
        return $this->setTitle(null);
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
     * Render the given template and create an expression from the content.
     *
     * @return ?ChartExpression the expression if the template is rendered; null on error
     */
    protected function createTemplateExpression(string $template, array $context = []): ?ChartExpression
    {
        try {
            $content = $this->twig->render($template, $context);
            $content = (string) \preg_replace(self::COMMENT_REGEX, '', $content);

            return ChartExpression::instance($content);
        } catch (\Twig\Error\Error) {
            return null;
        }
    }

    protected function getAxisOptions(): array
    {
        return [
            'labels' => [
                'style' => $this->getColorFontStyle('0.875rem'),
            ],
            'gridLineColor' => $this->getBorderColor(),
        ];
    }

    /**
     * Gets the body color.
     */
    protected function getBodyColor(): string
    {
        return 'var(--bs-body-color)';
    }

    /**
     * Gets the border color.
     */
    protected function getBorderColor(): string
    {
        return 'var(--bs-border-color)';
    }

    /**
     * Gets the font style for the given color and for the optional font size.
     *
     * @param ?string $fontSize the font size or null to use the body font size
     *
     * @return array an array with a font color, a font size, a font weight and a font family
     */
    protected function getColorFontStyle(?string $fontSize = null): array
    {
        return \array_merge(
            $this->getFontStyle($fontSize),
            ['color' => $this->getBodyColor()],
        );
    }

    /**
     * Gets the font style for the optional font size.
     *
     * @param ?string $fontSize the font size or null to use the body font size
     *
     * @return array an array with a font size, a font weight and a font family
     */
    protected function getFontStyle(?string $fontSize = null): array
    {
        return [
            'fontFamily' => 'var(--bs-body-font-family)',
            'fontWeight' => 'var(--bs-body-font-weight)',
            'fontSize' => $fontSize ?? 'var(--bs-body-font-size)',
        ];
    }

    /**
     * Gets the link color style.
     */
    protected function getLinkStyle(): array
    {
        return [
            'color' => 'var(--bs-link-hover-color)',
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
     * Initialize the chart options.
     */
    protected function initializeOptions(): void
    {
        $this->chart->merge([
            'backgroundColor' => 'var(--bs-body-bg)',
            'style' => $this->getFontStyle(),
            'renderTo' => self::CONTAINER,
        ]);

        $this->legend->merge([
            'itemHoverStyle' => $this->getLinkStyle(),
            'itemStyle' => $this->getColorFontStyle(),
        ]);

        $options = $this->getAxisOptions();
        $this->xAxis->merge($options);
        $this->yAxis->merge($options);

        $this->accessibility['enabled'] = false;
        $this->credits['enabled'] = false;

        $this->lang->merge([
            'decimalPoint' => FormatUtils::DECIMAL_SEP,
            'thousandsSep' => FormatUtils::THOUSANDS_SEP,
            'months' => \array_values(DateUtils::getMonths()),
            'weekdays' => \array_values(DateUtils::getWeekdays()),
            'shortMonths' => \array_values(DateUtils::getShortMonths()),
            'shortWeekdays' => \array_values(DateUtils::getShortWeekdays()),
        ]);
    }

    /**
     * Sets the tooltip options.
     */
    protected function setTooltipOptions(): static
    {
        $this->tooltip->merge([
            'backgroundColor' => 'var(--bs-light)',
            'style' => $this->getFontStyle('0.75rem'),
            'borderColor' => $this->getBorderColor(),
        ]);

        return $this;
    }
}
