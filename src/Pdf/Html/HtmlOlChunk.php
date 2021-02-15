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

namespace App\Pdf\Html;

/**
 * Specialized chunk for HTML ordered list (ol).
 *
 * @author Laurent Muller
 */
class HtmlOlChunk extends HtmlParentChunk
{
    /**
     * The list items will be numbered with lowercase letters.
     */
    public const TYPE_LETTER_LOWER = 'a';

    /**
     * The list items will be numbered with uppercase letters.
     */
    public const TYPE_LETTER_UPPER = 'A';

    /**
     * The list items will be numbered with numbers (default).
     */
    public const TYPE_NUMBER = '1';

    /**
     * The list items will be numbered with lowercase roman numbers.
     */
    public const TYPE_ROMAN_LOWER = 'i';

    /**
     * The list items will be numbered with uppercase roman numbers.
     */
    public const TYPE_ROMAN_UPPER = 'I';

    /**
     * The start counting.
     *
     * @var int
     */
    protected $start;

    /**
     * The numbered type.
     *
     * @var string
     */
    protected $type;
    /**
     * The number to roman map.
     *
     * @var array
     */
    private static $NUMBER_TO_ROMAN = [
        1000 => 'M',
        900 => 'CM',
        500 => 'D',
        400 => 'CD',
        100 => 'C',
        90 => 'XC',
        50 => 'L',
        40 => 'XL',
        10 => 'X',
        9 => 'IX',
        5 => 'V',
        4 => 'IV',
        1 => 'I',
    ];

    /**
     * Constructor.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     */
    public function __construct(string $name, ?HtmlParentChunk $parent = null)
    {
        parent::__construct($name, $parent);
        $this->type = self::TYPE_NUMBER;
        $this->start = 1;
    }

    /**
     * Gets the bullet text for the given child.
     *
     * @param AbstractHtmlChunk $chunk the child chunk to get text for
     *
     * @return string the bullet text
     */
    public function getBulletChunk(AbstractHtmlChunk $chunk): string
    {
        return $this->getBulletIndex($this->indexOf($chunk) + 1);
    }

    /**
     * Gets the bullet text for the given index.
     * <b>N.B.:</b> If the index is smaller than or equal to 0, an empty string ('') is returned.
     *
     * @param int $index the list item index (1 based)
     *
     * @return string the bullet text
     */
    public function getBulletIndex(int $index): string
    {
        if ($index <= 0) {
            return '';
        }

        // add start offset
        $index += $this->start - 1;

        switch ($this->type) {
            case self::TYPE_LETTER_LOWER:
                $text = '';
                while ($index > 26) {
                    $text .= 'a';
                    $index -= 26;
                }
                // 97 = 'a'
                $text .= \chr(96 + $index);
                break;

            case self::TYPE_LETTER_UPPER:
                $text = '';
                while ($index > 26) {
                    $text .= 'A';
                    $index -= 26;
                }
                // 65 = 'A'
                $text .= \chr(64 + $index);
                break;

            case self::TYPE_ROMAN_LOWER:
                $text = \strtolower($this->toRoman($index));
                break;

            case self::TYPE_ROMAN_UPPER:
                $text = $this->toRoman($index);
                break;

            default:
                $text = (string) $index;
                break;
        }

        return "{$text}.";
    }

    /**
     * Gets the bullet text for this number of children (if any).
     *
     * @return string the bullet text
     */
    public function getBulletMaximum(): string
    {
        return $this->getBulletIndex($this->count());
    }

    /**
     * Gets the start counting.
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Gets the numbered type.
     *
     * @return string one of the <code>TYPE_XX</code> constants
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the start counting.
     * <b>N.B.:</b> The minimum value is 1.
     */
    public function setStart(int $start): self
    {
        $this->start = \max($start, 1);

        return $this;
    }

    /**
     * Sets the numbered type.
     *
     * @param string $type one of the <code>TYPE_XX</code> constants
     */
    public function setType(string $type): self
    {
        switch ($type) {
            case self::TYPE_LETTER_LOWER:
            case self::TYPE_LETTER_UPPER:
            case self::TYPE_ROMAN_LOWER:
            case self::TYPE_ROMAN_UPPER:
                $this->type = $type;
                break;
            default:
                $this->type = self::TYPE_NUMBER;
                break;
        }

        return $this;
    }

    /**
     * Converts the value to a roman number.
     *
     * <b>N.B.:</b> If the value is smaller than or equal to 0, an empty string ('') is returned. If value is greather than 4999. the "<code>#N/A#</code>" string is returned.
     *
     * @param int $value the value to convert
     *
     * @return string the roman number
     */
    public static function toRoman(int $value): string
    {
        if ($value <= 0) {
            return '';
        }
        if ($value > 4999) {
            return '#N/A#';
        }

        $result = '';
        foreach (self::$NUMBER_TO_ROMAN as $limit => $glyph) {
            while ($value >= $limit) {
                $result .= $glyph;
                $value -= $limit;
            }
        }

        return $result;
    }
}
