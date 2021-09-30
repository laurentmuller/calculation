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

namespace App\Util;

/**
 * Class to get the file content, line by line; in the reverse order (last line first).
 *
 * @author Laurent Muller
 */
class ReverseReader
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
     * The file handler.
     *
     * @var ?resource
     */
    private $handle = null;

    /**
     * Constructor.
     *
     * @param string $filename the file name to open
     */
    public function __construct(string $filename)
    {
        $resource = \fopen($filename, 'r');
        if (\is_resource($resource)) {
            $this->handle = $resource;
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the resource file.
     *
     * @return bool true on success or false on failure
     */
    public function close(): bool
    {
        $result = true;
        if (null !== $this->handle) {
            $result = \fclose($this->handle);
            $this->handle = null;
        }

        return $result;
    }

    /**
     * Gets the current line.
     *
     * @return string|null the line, if any, null otherwise
     */
    public function current(): ?string
    {
        // valid?
        if (null === $this->handle) {
            return null;
        }

        $line = '';
        $started = false;
        $hasLine = false;

        while (!$hasLine) {
            // move
            if (0 === \ftell($this->handle)) {
                \fseek($this->handle, -1, \SEEK_END);
            } else {
                \fseek($this->handle, -2, \SEEK_CUR);
            }

            // read
            $read = ($char = \fgetc($this->handle));

            // check
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

        // move
        \fseek($this->handle, 1, \SEEK_CUR);

        // reverse
        return '' === $line ? null : \strrev($line);
    }

    /**
     * Returns if the resource file is open.
     *
     * @return bool true if open or false if not
     */
    public function isOpen(): bool
    {
        return null !== $this->handle;
    }
}
