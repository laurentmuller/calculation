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
 * Class to get the file content on the fly, line by line; in the reverse order (last line first).
 *
 * Example:
 *
 * <code>
 * $reader = new ReverseReader("path/to/file_name.txt");
 * foreach ($reader as $line) {
 *     echo $line . "\n";
 * }
 * </code>
 *
 * @extends AbstractReader<string>
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
     * @param \SplFileInfo|string $file   the file to open
     * @param bool                $binary true to open the file with binary mode
     */
    public function __construct(\SplFileInfo|string $file, bool $binary = false)
    {
        parent::__construct($file, $binary);
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
            $char = \fgetc($stream);
            if (false === $char) {
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

        if ('' !== $line) {
            return \strrev($line);
        }

        return null;
    }
}
