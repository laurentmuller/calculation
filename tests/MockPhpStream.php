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

namespace App\Tests;

/**
 * Allow overriding the 'php' stream wrapper functionality.
 *
 * This should be used sparingly and only when you need to mock php://input for tests
 */
#[\AllowDynamicProperties]
class MockPhpStream
{
    /**
     * The wrapper name protocol to be registered or unregistered.
     */
    private const PROTOCOL = 'php';

    /**
     * The current content.
     */
    private string $content = '';

    /**
     * The data content where the key is the path and value is the content.
     *
     * @var array<string, string>
     */
    private static array $data = [];

    /**
     * The current position in the content.
     */
    private int $index = 0;

    /**
     * The length of the current content.
     */
    private int $length = 0;

    /**
     * The current file path.
     */
    private string $path = '';

    /**
     * Registers this class as the 'php' stream wrapper.
     *
     * @throws \LogicException if unable to register this class as stream wrapper for the 'php' protocol
     */
    public static function register(): true
    {
        if (\in_array(self::PROTOCOL, \stream_get_wrappers(), true) && !\stream_wrapper_unregister(self::PROTOCOL)) {
            throw new \LogicException(\sprintf("Unable to unregister stream wrapper for the '%s' protocol.", self::PROTOCOL));
        }

        if (!\stream_wrapper_register(self::PROTOCOL, self::class)) {
            throw new \LogicException(\sprintf("Unable to register stream wrapper for the '%s' protocol.", self::PROTOCOL));
        }

        return true;
    }

    /**
     * Removes this class as the registered stream wrapper for 'php'.
     *
     * @return bool <code>true</code> on success or <code>false</code> on failure
     */
    public static function restore(): bool
    {
        return \stream_wrapper_restore(self::PROTOCOL);
    }

    public function stream_close(): void
    {
        if ('' !== $this->path && '' !== $this->content) {
            self::$data[$this->path] = $this->content;
            $this->path = $this->content = '';
            $this->index = 0;
        }
    }

    public function stream_eof(): bool
    {
        return $this->index >= $this->length;
    }

    public function stream_open(string $path, string $mode): true
    {
        if (\str_contains($mode, 'w')) {
            unset(self::$data[$path]);
        }

        $this->index = 0;
        $this->path = $path;
        $this->content = self::$data[$path] ?? '';
        $this->length = \strlen($this->content);

        return true;
    }

    public function stream_read(int $count): string|false
    {
        $len = \min($count, $this->length - $this->index);
        if ($len <= 0) {
            return false;
        }
        $data = \substr($this->content, $this->index, $len);
        $this->index += $len;

        return $data;
    }

    public function stream_seek(int $offset, int $whence): bool
    {
        $newIndex = match ($whence) {
            \SEEK_SET => $offset,
            \SEEK_CUR => $this->index + $offset,
            \SEEK_END => $this->length + $offset,
            default => -1,
        };
        if ($newIndex < 0 || $newIndex >= $this->length) {
            return false;
        }
        $this->index = $newIndex;

        return true;
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function stream_tell(): int
    {
        return $this->index;
    }

    public function stream_write(string $data): int
    {
        $len = \strlen($data);
        if ($len > 0) {
            $this->content .= $data;
            $this->length += $len;
        }

        return $len;
    }
}
