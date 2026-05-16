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

namespace App\Pivot;

use App\Interfaces\EnumSortableInterface;
use App\Pivot\Aggregator\AbstractAggregator;
use App\Pivot\Aggregator\AverageAggregator;
use App\Pivot\Aggregator\CountAggregator;
use App\Pivot\Aggregator\MaxAggregator;
use App\Pivot\Aggregator\MinAggregator;
use App\Pivot\Aggregator\SumAggregator;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;

/**
 * The pivot operation (function) enumeration.
 *
 * @implements EnumSortableInterface<PivotOperation>
 * @implements PdfEnumDefaultInterface<PivotOperation>
 */
#[ReadableEnum(prefix: 'pivot.operation.', useValueAsDefault: true)]
enum PivotOperation: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /** The average operation. */
    case AVERAGE = 'average';
    /** The count operation. */
    case COUNT = 'count';
    /** The maximum operation. */
    case MAX = 'max';
    /** The minimum operation. */
    case MIN = 'min';
    /** The sum operation. */
    case SUM = 'sum';

    /**
     * Gets the aggregator class name.
     *
     * @return class-string<AbstractAggregator>
     */
    public function getAggregator(): string
    {
        return match ($this) {
            self::AVERAGE => AverageAggregator::class,
            self::COUNT => CountAggregator::class,
            self::MAX => MaxAggregator::class,
            self::MIN => MinAggregator::class,
            self::SUM => SumAggregator::class,
        };
    }

    #[\Override]
    public static function getDefault(): PivotOperation
    {
        return self::SUM;
    }

    /**
     * Gets a value indicating whether the operation returns an integer or a float as results.
     *
     * @return bool true if the operation returns an integer, false if is a float
     */
    public function isInt(): bool
    {
        return match ($this) {
            self::COUNT => true,
            default => false,
        };
    }

    #[\Override]
    public static function sorted(): array
    {
        return [
            self::SUM,
            self::COUNT,
            self::AVERAGE,
            self::MAX,
            self::MIN,
        ];
    }
}
