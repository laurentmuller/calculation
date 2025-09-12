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
 * Example:
 *
 * <code>
 * $reader = new CsvReader("path/to/file.csv");
 * foreach ($reader as $key => $data) {
 *    echo $data[2] ."\n";
 * }
 * </code>
 *
 * @extends AbstractReader<string[]>
 */
class CSVReader extends AbstractReader
{
    /**
     * @param \SplFileInfo|string|resource $file      the CSV file to open or an opened resource
     * @param bool                         $binary    true to open the file with binary mode
     * @param int                          $length    the line length.
     *                                                Must be greater than the longest line (in characters) to be found
     *                                                in the CSV file (allowing for trailing line-end characters).
     *                                                Setting it to 0, the maximum line length is not limited, which is
     *                                                slightly slower.
     * @param string                       $separator the field delimiter (one character only)
     * @param string                       $enclosure the field enclosure character (one character only)
     * @param string                       $escape    the escape character (one character only)
     *
     * @phpstan-param int<0, max> $length
     */
    public function __construct(
        mixed $file,
        bool $binary = false,
        private readonly int $length = 0,
        private readonly string $separator = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\'
    ) {
        parent::__construct($file, $binary);
    }

    #[\Override]
    protected function getNextData($stream): ?array
    {
        $data = \fgetcsv($stream, $this->length, $this->separator, $this->enclosure, $this->escape);

        /** @phpstan-var non-empty-list<string>|null */
        return \is_array($data) ? $data : null;
    }
}
