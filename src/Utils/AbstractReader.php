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
 * @implements \Iterator<int, TValue>
 */
abstract class AbstractReader implements \Iterator
{
    /**
     * The first rewind call.
     */
    private bool $canRewind;

    /**
     * The current data.
     *
     * @var ?TValue
     */
    private mixed $data = null;

    /**
     * The current key (line index).
     */
    private int $key = 0;

    /**
     * The resource file.
     *
     * @var resource|closed-resource|false
     */
    private mixed $stream;

    /**
     * Constructor.
     *
     * @param \SplFileInfo|string|resource $file the file to open
     */
    public function __construct(mixed $file)
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }
        if (\is_resource($file)) {
            $this->canRewind = false;
            $this->stream = $file;
        } else {
            $this->canRewind = true;
            $this->stream = \fopen($file, 'r');
        }
        if (\is_resource($this->stream)) {
            $this->parseNextLine();
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
            $this->key = 0;
            $this->data = null;
            $this->stream = false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return TValue
     */
    public function current(): mixed
    {
        return $this->data;
    }

    /**
     * Returns if the resource file is open.
     */
    public function isOpen(): bool
    {
        return \is_resource($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->parseNextLine();
        if ($this->valid()) {
            ++$this->key;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        try {
            if ($this->canRewind && \is_resource($this->stream) && \rewind($this->stream)) {
                $this->key = 0;
                $this->parseNextLine();
            }
        } catch (\Exception) {
            $this->canRewind = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return null !== $this->data;
    }

    /**
     * Gets data from the given resource.
     *
     * @param resource $stream
     *
     * @return ?TValue the parsed data or null if none
     */
    abstract protected function getNextData($stream): mixed;

    /**
     * Parse the next line.
     */
    protected function parseNextLine(): void
    {
        $this->data = null;
        if (\is_resource($this->stream) && !\feof($this->stream)) {
            $this->data = $this->getNextData($this->stream);
        }
    }
}
