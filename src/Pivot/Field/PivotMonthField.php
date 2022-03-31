<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pivot\Field;

use App\Util\DateUtils;

/**
 * Pivot field that map month values (1...12) to month names (january, february, etc...).
 *
 * @author Laurent Muller
 */
class PivotMonthField extends PivotDateField
{
    /**
     * The month names.
     *
     * @var string[]
     */
    private array $names;

    /**
     * Constructor.
     *
     * @param string      $name  the field name
     * @param string|null $title the field title
     * @param bool        $short true to display the short month name, false to display the full month name
     */
    public function __construct(protected string $name, protected ?string $title = null, bool $short = false)
    {
        parent::__construct($name, self::PART_MONTH, $title);
        $this->names = $short ? DateUtils::getShortMonths() : DateUtils::getMonths();
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayValue(mixed $value): mixed
    {
        if (\is_int($value) && \array_key_exists($value, $this->names)) {
            return $this->names[$value];
        }

        return parent::getDisplayValue($value);
    }
}
