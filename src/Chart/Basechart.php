<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Chart;

use App\Service\ApplicationService;
use App\Traits\FormatterTrait;
use App\Utils\DateUtils;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Basic chart with properties shortcut.
 *
 * @method style(array $options) set the style options.
 * @method xAxis(array $options) set the x axis options.
 * @method yAxis(array $options) set the y axis options.
 *
 * @property mixed $xAxis the x axis options.
 * @property mixed $yAxis the y axis options.
 */
class Basechart extends Highchart
{
    use FormatterTrait;

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
    public function __construct(ApplicationService $application)
    {
        parent::__construct();
        $this->application = $application;
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
     *
     * @param translatorInterface $translator the translator used for messages
     */
    public function initLangOptions(TranslatorInterface $translator): self
    {
        // array options
        $this->setLangOption('months', \array_values(DateUtils::getMonths()))
            ->setLangOption('shortMonths', \array_values(DateUtils::getShortMonths()))
            ->setLangOption('weekdays', \array_values(DateUtils::getWeekdays()))
            ->setLangOption('shortWeekdays', \array_values(DateUtils::getShortWeekdays()));

        // format options
        $this->setLangOption('thousandsSep', $this->getDefaultGrouping())
            ->setLangOption('decimalPoint', $this->getDefaultDecimal());

        // translated options
//         $options = [
//             'downloadPNG',
//             'downloadJPEG',
//             'downloadPDF',
//             'downloadSVG',

//             'exportButtonTitle',
//             'printButtonTitle',
//             'resetZoomTitle',
//             'contextButtonTitle',

//             'noData',
//             'loading',
//             'resetZoom',

//             'rangeSelectorFrom',
//             'rangeSelectorTo',
//             'rangeSelectorZoom',
//             'printChart',
//             'drillUpText',
//         ];
//         foreach ($options as $option) {
//             $value = $translator->trans($option, [], 'chart');
//             $this->setLangOption($option, $value);
//         }

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
     * @param string $type the chart type to set like 'pie' or 'column'
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
