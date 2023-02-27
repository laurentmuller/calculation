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

namespace App\Util;

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
     * The current data.
     *
     * @var ?TValue
     */
    private mixed $data = null;

    /**
     * The current position (line index).
     */
    private int $position = 0;

    /**
     * The resource file.
     *
     * @var resource|closed-resource|false
     */
    private $stream; // @phpstan-ignore-line

    /**
     * Constructor.
     *
     * @param string $filename the file name to open
     */
    public function __construct(string $filename)
    {
        $this->stream = \fopen($filename, 'r');
        if (\is_resource($this->stream)) {
            $this->parseNextLine();
        }
    }

    /**
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
            $this->position = 0;
            $this->data = null;
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
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
        $this->parseNextLine();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        if (\is_resource($this->stream) && \rewind($this->stream)) {
            $this->position = 0;
            $this->parseNextLine();
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
