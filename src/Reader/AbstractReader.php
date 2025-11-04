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
     * @param \SplFileInfo|string|resource $file the file to open or an opened resource
     */
    public function __construct(mixed $file)
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }
        $this->stream = \is_string($file) ? \fopen($file, 'r') : $file;
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
    #[\Override]
    public function getIterator(): \Generator
    {
        while (\is_resource($this->stream) && !\feof($this->stream)
                    && null !== $data = $this->getNextData($this->stream)) {
            yield $data;
        }
    }

    /**
     * Returns if this underlying resource file is open.
     *
     * @phpstan-assert-if-true resource $this->stream
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
