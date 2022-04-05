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
    final public const TYPE_LETTER_LOWER = 'a';

    /**
     * The list items will be numbered with uppercase letters.
     */
    final public const TYPE_LETTER_UPPER = 'A';

    /**
     * The list items will be numbered with numbers (default).
     */
    final public const TYPE_NUMBER = '1';

    /**
     * The list items will be numbered with lowercase roman numbers.
     */
    final public const TYPE_ROMAN_LOWER = 'i';

    /**
     * The list items will be numbered with uppercase roman numbers.
     */
    final public const TYPE_ROMAN_UPPER = 'I';

    /**
     * The start counting.
     */
    protected int $start;

    /**
     * The numbered type.
     */
    protected string $type;

    /**
     * Constructor.
     *
     * @param string               $name   the tag name
     * @param HtmlParentChunk|null $parent the parent chunk
     */
    public function __construct(protected string $name, ?HtmlParentChunk $parent = null)
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
                $text = \strtolower(self::toRoman($index));
                break;

            case self::TYPE_ROMAN_UPPER:
                $text = self::toRoman($index);
                break;

            default:
                $text = (string) $index;
                break;
        }

        return "$text.";
    }

    /**
     * Gets the bullet text for this number of children (if any).
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
        $this->type = match ($type) {
            self::TYPE_LETTER_LOWER,
            self::TYPE_LETTER_UPPER,
            self::TYPE_ROMAN_LOWER,
            self::TYPE_ROMAN_UPPER => $type,
            default => self::TYPE_NUMBER,
        };

        return $this;
    }

    /**
     * Converts the value to a roman number.
     *
     * <b>N.B.:</b> If value is smaller than or equal to 0 or if value is greather than 4999, the "<code>#N/A#</code>" string is returned.
     *
     * @param int $number the value to convert
     *
     * @return string the roman number
     */
    public static function toRoman(int $number): string
    {
        // out of range?
        if ($number <= 0 || $number > 4999) {
            return '#N/A#';
        }

        // lookup array that we will use to traverse the number
        /** @psalm-var array<string, int> $lookup */
        static $lookup = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        foreach ($lookup as $roman => $value) {
            // look for number of matches
            $multiplier = (int) ($number / $value);

            // concatenate characters
            $result .= \str_repeat($roman, $multiplier);

            // substract that from the number
            $number %= $value;
        }

        return $result;
    }
}
