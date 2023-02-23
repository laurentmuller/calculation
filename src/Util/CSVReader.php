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

namespace App\Util;

/**
 * Class to read CSV file on the fly.
 *
 * Example:
 *
 * <code>
 *      $reader = new CsvReader("path/to/file.csv");
 *
 *      foreach ($reader as $data) {
 *          echo $data[2] ."\n";
 *      }
 * </code>
 *
 * @extends AbstractReader<string[]>
 */
class CSVReader extends AbstractReader
{
    /**
     * Constructor.
     *
     * @param string $filename  the CSV file name to open
     * @param int    $length    Must be greater than the longest line (in characters) to be found in
     *                          the CSV file (allowing for trailing line-end characters). Setting it to 0,
     *                          the maximum line length is not limited, which is slightly slower.
     * @param string $separator the field delimiter (one character only)
     * @param string $enclosure the field enclosure character (one character only)
     * @param string $escape    the escape character (one character only)
     *
     * @psalm-param int<0, max> $length
     */
    public function __construct(
        string $filename,
        private readonly int $length = 0,
        private readonly string $separator = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\'
    ) {
        parent::__construct($filename);
    }

    /**
     * @param resource $stream
     *
     * @return string[]|null
     */
    protected function parseData($stream): ?array
    {
        if (\is_array($data = \fgetcsv($stream, $this->length, $this->separator, $this->enclosure, $this->escape))) {
            return $data;
        }

        return null;
    }
}
