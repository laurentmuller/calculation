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
    /*
     * The key of the current iteration.
     */
    private int $key = 0;

    /**
     * The resource file.
     *
     * @var resource|closed-resource|false
     */
    private mixed $stream;

    /**
     * @param string|resource $file the file to open or an opened resource
     */
    public function __construct(mixed $file)
    {
        $this->stream = \is_string($file) ? \fopen($file, 'r') : $file;
    }

    /**
     * By default, close the resource file.
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
        while ($this->isValid() && null !== $data = $this->nextData($this->stream)) {
            yield $this->key++ => $data;
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
     * Skip the given number of lines.
     *
     * @param int<1, max> $lines the lines to skip
     *
     * @return int<0, max> the skipped lines
     */
    public function skip(int $lines = 1): int
    {
        $count = 0;
        while ($lines-- > 0 && $this->isValid() && null !== $this->nextData($this->stream)) {
            ++$count;
        }
        $this->key += $count;

        return $count;
    }

    /**
     * Returns a value indicating if this stream is valid.
     *
     * @return bool true if this stream is valid
     *
     * @phpstan-assert-if-true resource $this->stream
     */
    protected function isValid(): bool
    {
        return \is_resource($this->stream) && !\feof($this->stream);
    }

    /**
     * Gets the next parsed data from the given resource.
     *
     * @param resource $stream the stream to get data from
     *
     * @return ?TValue the parsed data or null if none
     */
    abstract protected function nextData($stream): mixed;
}
