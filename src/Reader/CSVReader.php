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

namespace App\Reader;

/**
 * Class to read CSV file on the fly.
 *
 * @extends AbstractReader<string[]>
 */
class CSVReader extends AbstractReader
{
    /**
     * The default field enclosure character.
     */
    public const DEFAULT_ENCLOSURE = '"';

    /**
     * The default escape character.
     */
    public const DEFAULT_ESCAPE = '\\';

    /**
     * The default field separator character.
     */
    public const DEFAULT_SEPARATOR = ',';

    /**
     * @param \SplFileInfo|string|resource $file      the CSV file to open or an opened resource
     * @param int<0, max>                  $length    the line length.
     *                                                Must be greater than the longest line (in characters) to be found
     *                                                in the CSV file (allowing for trailing line-end characters).
     *                                                Setting it to 0, the maximum line length is not limited, which is
     *                                                slightly slower.
     * @param string                       $separator the field delimiter. It must be a single byte character.
     * @param string                       $enclosure the field enclosure character. It must be a single byte character.
     * @param string                       $escape    the escape character. It must be a single byte character or
     *                                                the empty string. The empty string ("")  disables the proprietary
     *                                                escape mechanism.
     *
     * @throws \InvalidArgumentException if the separator or the enclosure is not a single byte character,
     *                                   or if the escape is not a single byte character or is not empty
     */
    public function __construct(
        mixed $file,
        private readonly int $length = 0,
        private readonly string $separator = self::DEFAULT_SEPARATOR,
        private readonly string $enclosure = self::DEFAULT_ENCLOSURE,
        private readonly string $escape = self::DEFAULT_ESCAPE,
    ) {
        if (1 !== \strlen($separator)) {
            throw new \InvalidArgumentException('Field separator must be a single byte character.');
        }
        $length = \strlen($escape);
        if (0 !== $length && 1 !== $length) {
            throw new \InvalidArgumentException('Escape character must be a single byte character or an empty string.');
        }
        if (1 !== \strlen($enclosure)) {
            throw new \InvalidArgumentException('Field enclosure character must be a single byte character.');
        }
        parent::__construct($file);
    }

    /**
     * Creates a new instance.
     *
     * @param \SplFileInfo|string|resource $file      the CSV file to open or an opened resource
     * @param int<0, max>                  $length    the line length.
     *                                                Must be greater than the longest line (in characters) to be found
     *                                                in the CSV file (allowing for trailing line-end characters).
     *                                                Setting it to 0, the maximum line length is not limited, which is
     *                                                slightly slower.
     * @param string                       $separator the field delimiter. It must be a single byte character.
     * @param string                       $enclosure the field enclosure character. It must be a single byte character.
     * @param string                       $escape    the escape character. It must be a single byte character or
     *                                                the empty string.
     *                                                The empty string ("")  disables the proprietary
     *
     * @throws \InvalidArgumentException if the separator or the enclosure is not a single byte character,
     *                                   or if the escape is not a single byte character or is not empty
     */
    public static function instance(
        mixed $file,
        int $length = 0,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE
    ): self {
        return new self($file, $length, $separator, $enclosure, $escape);
    }

    #[\Override]
    protected function nextData($stream): ?array
    {
        $data = \fgetcsv($stream, $this->length, $this->separator, $this->enclosure, $this->escape);

        /** @phpstan-var non-empty-list<string>|null */
        return \is_array($data) ? $data : null;
    }
}
