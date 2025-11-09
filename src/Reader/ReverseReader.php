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
 * @extends AbstractReader<string>
 */
class ReverseReader extends AbstractReader
{
    /**
     * Creates a new instance.
     *
     * @param \SplFileInfo|string|resource $file the file to open
     */
    public static function instance(mixed $file): self
    {
        return new self($file);
    }

    #[\Override]
    protected function nextData($stream): ?string
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
                case "\n": // line feed
                case "\r": // carriage return
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
