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
 * Abstract class to get the file content on the fly.
 *
 * @template TValue
 *
 * @implements \IteratorAggregate<int, TValue>
 */
abstract class AbstractReader implements \IteratorAggregate
{
    /**
     * The resource file.
     *
     * @var resource|closed-resource|false
     */
    private mixed $stream;

    /**
     * Constructor.
     *
     * @param \SplFileInfo|string|resource $file   the file to open
     * @param bool                         $binary true to open the file with binary mode
     */
    public function __construct(mixed $file, bool $binary = false)
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }
        if (\is_resource($file)) {
            $this->stream = $file;
        } else {
            $mode = $binary ? 'rb' : 'r';
            $this->stream = \fopen($file, $mode);
        }
    }

    /**
     * By default, close the resource file.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the resource file.
     */
    public function close(): void
    {
        if (\is_resource($this->stream) && \fclose($this->stream)) {
            $this->stream = false;
        }
    }

    /**
     * @return \Generator<int, TValue>
     */
    public function getIterator(): \Generator
    {
        while (\is_resource($this->stream) && !\feof($this->stream) && null !== $data = $this->getNextData($this->stream)) {
            yield $data;
        }
    }

    /**
     * Returns if the resource file is open.
     */
    public function isOpen(): bool
    {
        return \is_resource($this->stream);
    }

    /**
     * Gets data from the given resource.
     *
     * @param resource $stream
     *
     * @return ?TValue the parsed data or null if none
     */
    abstract protected function getNextData($stream): mixed;
}
