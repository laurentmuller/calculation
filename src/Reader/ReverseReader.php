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
     * Creates a new instance.
     *
     * @param \SplFileInfo|string $file   the file to open
     * @param bool                $binary true to open the file with binary mode
     */
    public static function instance(\SplFileInfo|string $file, bool $binary = false): self
    {
        return new self($file, $binary);
    }

    #[\Override]
    protected function getNextData($stream): ?string
    {
        $line = '';
        $started = false;
        $hasLine = false;

        while (!$hasLine) {
            // move
            0 === \ftell($stream) ? \fseek($stream, -1, \SEEK_END) : \fseek($stream, -2, \SEEK_CUR);
            // read
            $char = \fgetc($stream);
            switch ($char) {
                case false:
                    $hasLine = true;
                    break;
                case self::LINE_FEED:
                case self::CARRIAGE_RETURN:
                    $started ? $hasLine = true : $started = true;
                    break;
                default:
                    if ($started) {
                        $line = $char . $line;
                    }
                    break;
            }
        }
        \fseek($stream, 1, \SEEK_CUR);

        return '' === $line ? null : $line;
    }
}
