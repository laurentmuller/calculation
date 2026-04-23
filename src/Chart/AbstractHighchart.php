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

use App\Parameter\ApplicationParameters;
use App\Traits\MathTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use HighchartsBundle\Highcharts\ChartExpression;
use HighchartsBundle\Highcharts\Highchart;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Extends the Highchart with method shortcuts.
 */
class AbstractHighchart extends Highchart implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    /** The default identifier of the div where to render the chart. */
    public const string CONTAINER = 'chartContainer';

    private const string COMMENT_REGEX = '/\/\*(.|[\r\n])*?\*\//m';

    public function __construct(
        protected readonly ApplicationParameters $parameters,
        protected readonly UrlGeneratorInterface $generator,
        protected readonly Environment $twig
    ) {
        parent::__construct();
        $this->initializeOptions();
    }

    /**
     * Disable the accessibility.
     */
    public function disableAccessibility(): static
    {
        $this->accessibility['enabled'] = false;

        return $this;
    }

    /**
     * Disable the credit.
     */
    public function disableCredit(): static
    {
        $this->credits['enabled'] = false;

        return $this;
    }

    /**
     * Hides the chart title.
     */
    public function hideTitle(): static
    {
        $this->title['text'] = null;

        return $this;
    }

    /**
     * Sets the chart type.
     */
    public function setType(ChartType|string $type): static
    {
        if ($type instanceof ChartType) {
            $type = $type->value;
        }
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
            $content = StringUtils::pregReplace(self::COMMENT_REGEX, '', $content);

            return ChartExpression::instance($content);
        } catch (Error) {
            return null;
        }
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

    protected function getClickExpression(): ChartExpression
    {
        return ChartExpression::instance('function() {location.href = this.url;}');
    }

    /**
     * Gets the font style for the given color and the optional font size.
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
     * Gets the minimum margin class.
     */
    protected function getMarginClass(float $value): string
    {
        return $this->parameters->isMarginBelow($value) ? 'text-danger' : '';
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    protected function getMinMargin(): float
    {
        return $this->parameters->getMinMargin();
    }

    /**
     * Initialize options.
     */
    protected function initializeOptions(): void
    {
        $this->setChartOptions()
            ->setTooltipOptions()
            ->setLegendOptions()
            ->setLangOptions()
            ->setAxisOptions()
            ->disableAccessibility()
            ->disableCredit()
            ->hideTitle();
    }

    /**
     * Sets the x and y axes options.
     */
    protected function setAxisOptions(): static
    {
        $options = [
            'labels' => [
                'style' => $this->getColorFontStyle('0.875rem'),
            ],
            'gridLineColor' => $this->getBorderColor(),
        ];
        $this->xAxis->merge($options);
        $this->yAxis->merge($options);

        return $this;
    }

    /**
     * Sets the chart options.
     */
    protected function setChartOptions(): static
    {
        $this->chart->merge([
            'renderTo' => self::CONTAINER,
            'style' => $this->getFontStyle(),
            'backgroundColor' => 'var(--bs-body-bg)',
            'events' => [
                'load' => new ChartExpression('function(e) {chartLoaded(e);}'),
            ],
        ]);

        return $this;
    }

    /**
     * Sets the lang options.
     */
    protected function setLangOptions(): static
    {
        $this->lang->merge([
            'decimalPoint' => FormatUtils::DECIMAL_SEP,
            'thousandsSep' => FormatUtils::THOUSANDS_SEP,
            'months' => \array_values(DateUtils::getMonths()),
            'weekdays' => \array_values(DateUtils::getWeekdays()),
            'shortMonths' => \array_values(DateUtils::getShortMonths()),
            'shortWeekdays' => \array_values(DateUtils::getShortWeekdays()),
        ]);

        return $this;
    }

    /**
     * Sets the legend options.
     */
    protected function setLegendOptions(): static
    {
        $this->legend->merge([
            'itemStyle' => $this->getColorFontStyle(),
            'itemHoverStyle' => $this->getLinkStyle(),
            'itemHiddenStyle' => [
                'color' => 'var(--bs-secondary)',
            ],
        ]);

        return $this;
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
            'borderRadius' => 0,
            'useHTML' => true,
            'shared' => true,
        ]);

        return $this;
    }
}
