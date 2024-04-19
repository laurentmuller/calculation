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
use HighchartsBundle\Highcharts\Highchart;
use Laminas\Json\Expr;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * Extends the Highchart with method shortcuts.
 */
class AbstractHighchart extends Highchart implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceSubscriberTrait;
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

    /**
     * @psalm-suppress TooManyArguments
     */
    public function __construct(protected readonly ApplicationService $application)
    {
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
     */
    protected function createTemplateExpression(Environment $twig, string $template): ?Expr
    {
        try {
            $content = $twig->render($template);
            $content = (string) \preg_replace(self::COMMENT_REGEX, '', $content);

            return self::createExpression($content);
        } catch (\Twig\Error\Error) {
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
            'fontSize' => $fontSize ?? 'var(--bs-body-font-size)',
            'fontWeight' => 'var(--bs-body-font-weight)',
            'fontFamily' => 'var(--bs-body-font-family)',
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
     * Sets the tooltip options.
     */
    protected function setTooltipOptions(): static
    {
        $this->tooltip->merge([
            'borderRadius' => 5,
            'backgroundColor' => 'var(--bs-light)',
            'style' => $this->getFontStyle('0.75rem'),
            'borderColor' => $this->getBorderColor(),
        ]);

        return $this;
    }

    private function getAxisOptions(): array
    {
        return [
            'labels' => [
                'style' => $this->getColorFontStyle('0.875rem'),
            ],
            'gridLineColor' => $this->getBorderColor(),
        ];
    }

    /**
     * Initialize the chart options.
     *
     * @psalm-suppress TooManyArguments
     */
    private function initializeOptions(): void
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

        $axisOptions = $this->getAxisOptions();
        $this->xAxis->merge($axisOptions);
        $this->yAxis->merge($axisOptions);

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
}
