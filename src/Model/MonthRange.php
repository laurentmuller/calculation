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
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

class MonthRange
{
    #[Assert\NotNull]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateUtils::FORM_DATE_FORMAT])]
    public DatePoint $from {
        set => DateUtils::getStartOfMonth($value);
    }
    #[Assert\NotNull]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DateUtils::FORM_DATE_FORMAT])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'from', message: 'month_range.end_date')]
    public DatePoint $to {
        set => DateUtils::getEndOfMonth($value);
    }

    public function __construct()
    {
        $date = DateUtils::createDate();
        $this->from = $date;
        $this->to = $date;
    }

    public function months(): int
    {
        return $this->from->diff($this->to)->m + 1;
    }

    public function withFrom(DatePoint $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function withTo(DatePoint $to): self
    {
        $this->to = $to;

        return $this;
    }
}
