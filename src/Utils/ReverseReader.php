<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Utils;

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
     * @var resource|bool
     */
    private $handle;

    /**
     * Constructor.
     *
     * @param string $filename the file name to open
     */
    public function __construct(string $filename)
    {
        $this->handle = \fopen($filename, 'r');
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
        if ($this->isOpen()) {
            $result = \fclose($this->handle);
            $this->handle = false;
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
        if (!$this->isOpen()) {
            return null;
        }

        $line = '';
        $started = false;
        $hasLine = false;

        while (!$hasLine) {
            // move
            if (0 === \ftell($this->handle)) {
                \fseek($this->handle, -1, SEEK_END);
            } else {
                \fseek($this->handle, -2, SEEK_CUR);
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
        \fseek($this->handle, 1, SEEK_CUR);

        // reverse
        return 0 === \strlen($line) ? null : \strrev($line);
    }

    /**
     * Returns if the resource file is open.
     *
     * @return bool true if open or false if not
     */
    public function isOpen(): bool
    {
        return false !== $this->handle;
    }
}
