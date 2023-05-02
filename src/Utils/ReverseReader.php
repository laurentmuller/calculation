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

namespace App\Utils;

/**
 * Class to get the file content on the fly, line by line; in the reverse order (last line first).
 *
 * Example:
 *
 * <code>
 * $reader = new ReverseReader("path/to/file_name.txt");
 * foreach ($reader as $data) {
 *     echo $data ."\n";
 * }
 * </code>
 *
 * @extends AbstractReader<string|null>
 */
class ReverseReader extends AbstractReader
{
    /**
     * The carriage return character.
     */
    private const CARRIAGE_RETURN = "\r";

    /**
     * The line feed character.
     */
    private const LINE_FEED = "\n";

    /**
     * Constructor.
     *
     * @param \SplFileInfo|string $file the file to open
     */
    public function __construct(\SplFileInfo|string $file)
    {
        parent::__construct($file);
    }

    protected function getNextData($stream): ?string
    {
        $line = '';
        $started = false;
        $hasLine = false;

        while (!$hasLine) {
            // move
            if (0 === \ftell($stream)) {
                \fseek($stream, -1, \SEEK_END);
            } else {
                \fseek($stream, -2, \SEEK_CUR);
            }
            $read = ($char = \fgetc($stream));
            if (false === $read) {
                $hasLine = true;
            } elseif (self::LINE_FEED === $char || self::CARRIAGE_RETURN === $char) {
                if ($started) {
                    $hasLine = true;
                } else {
                    $started = true;
                }
            } elseif ($started) {
                $line .= $char;
            }
        }
        \fseek($stream, 1, \SEEK_CUR);

        return '' === $line ? null : \strrev($line);
    }
}
