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

namespace App\Service;

/**
 * Service to parse CSV data from a string or a resource.
 */
readonly class CsvService
{
    /**
     * The default field enclosure character.
     */
    public const string DEFAULT_ENCLOSURE = '"';

    /**
     * The default escape character.
     */
    public const string DEFAULT_ESCAPE = '\\';

    /**
     * The default field separator character.
     */
    public const string DEFAULT_SEPARATOR = ',';

    /**
     * @param string       $separator the field delimiter. It must be a single byte character.
     * @param string       $enclosure the field enclosure character. It must be a single byte character.
     * @param string       $escape    the escape character. It must be a single byte character or
     *                                the empty string. The empty string ("")  disables the proprietary
     *                                escape mechanism.
     * @param ?int<0, max> $length    the line length (used only to read streams).
     *                                Must be greater than the longest line (in characters) to be found
     *                                in the CSV file (allowing for trailing line-end characters).
     *                                Setting it to 0 or null, the maximum line length is not limited, which is
     *                                slightly slower.
     *
     * @throws \InvalidArgumentException if the separator or the enclosure is not a single byte character,
     *                                   or if the escape is not a single byte character or is not empty
     */
    public function __construct(
        public string $separator = self::DEFAULT_SEPARATOR,
        public string $enclosure = self::DEFAULT_ENCLOSURE,
        public string $escape = self::DEFAULT_ESCAPE,
        public ?int $length = null,
    ) {
        if (1 !== \strlen($separator)) {
            throw new \InvalidArgumentException('Field separator must be a single byte character.');
        }
        if (1 !== \strlen($enclosure)) {
            throw new \InvalidArgumentException('Field enclosure character must be a single byte character.');
        }
        $length = \strlen($escape);
        if (0 !== $length && 1 !== $length) {
            throw new \InvalidArgumentException('Escape character must be a single byte character or an empty string.');
        }
    }

    /**
     * Creates a new instance.
     *
     * @param string       $separator the field delimiter. It must be a single byte character.
     * @param string       $enclosure the field enclosure character. It must be a single byte character.
     * @param string       $escape    the escape character. It must be a single byte character or
     *                                the empty string. The empty string ("")  disables the proprietary
     *                                escape mechanism.
     * @param ?int<0, max> $length    the line length (used only to read streams).
     *                                Must be greater than the longest line (in characters) to be found
     *                                in the CSV file (allowing for trailing line-end characters).
     *                                Setting it to 0 or null, the maximum line length is not limited, which is
     *                                slightly slower.
     *
     * @throws \InvalidArgumentException if the separator or the enclosure is not a single byte character,
     *                                   or if the escape is not a single byte character or is not empty
     */
    public static function instance(
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
        ?int $length = null,
    ): self {
        return new self($separator, $enclosure, $escape, $length);
    }

    /**
     * Parse the given string or resource.
     *
     * @param string|resource $data the string or the resource to parse
     *
     * @return non-empty-list<string>|null an indexed array containing the fields read or null on error
     */
    public function parse(mixed $data): ?array
    {
        if (\is_string($data)) {
            $values = \str_getcsv($data, $this->separator, $this->enclosure, $this->escape);
        } else {
            $values = \fgetcsv($data, $this->length, $this->separator, $this->enclosure, $this->escape);
        }
        if (false === $values || null === $values[0]) {
            return null;
        }

        /** @phpstan-var non-empty-list<string> */
        return $values;
    }
}
