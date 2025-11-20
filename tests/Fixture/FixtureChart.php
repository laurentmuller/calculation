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

namespace App\Tests\Fixture;

use App\Chart\AbstractHighchart;
use HighchartsBundle\Highcharts\ChartExpression;

/**
 * Chart for tests with public methods.
 */
class FixtureChart extends AbstractHighchart
{
    #[\Override]
    public function createTemplateExpression(string $template, array $context = []): ?ChartExpression
    {
        return parent::createTemplateExpression($template, $context);
    }

    #[\Override]
    public function getClickExpression(): ChartExpression
    {
        return parent::getClickExpression();
    }

    #[\Override]
    public function getMarginClass(float $value): string
    {
        return parent::getMarginClass($value);
    }

    #[\Override]
    public function getMinMargin(): float
    {
        return parent::getMinMargin();
    }

    #[\Override]
    public function setTooltipOptions(): static
    {
        return parent::setTooltipOptions();
    }
}
