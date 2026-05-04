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

namespace App\Model;

use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;

class MonthChartDataItem extends ChartDataItem
{
    public readonly DatePoint $date;

    public function __construct(
        int $count,
        float $items,
        float $total,
        public readonly int $year,
        public readonly int $month,
    ) {
        parent::__construct($count, $items, $total);
        $this->date = DateUtils::createDatePoint(\sprintf('%d-%d-10', $this->year, $this->month));
    }

    public function getLongDate(): string
    {
        return $this->formatDate('MMMM Y');
    }

    public function getSearchDate(): string
    {
        return $this->formatDate('M.Y');
    }

    public function getShortDate(): string
    {
        return $this->formatDate('MMM Y');
    }

    private function formatDate(string $pattern): string
    {
        return FormatUtils::formatDate($this->date, \IntlDateFormatter::NONE, $pattern);
    }
}
