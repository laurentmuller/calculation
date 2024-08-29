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
 * Allows you to override the 'php' stream wrapper functionality.
 *
 * This should be used sparingly and only when you need to mock php://input for a test
 */
#[\AllowDynamicProperties]
class MockPhpStream
{
    /**
     * The protocol.
     */
    private const PROTOCOL = 'php';

    /**
     * The current content.
     */
    private string $content = '';

    /**
     * The data content.
     *
     * @var array<string, string>
     */
    private static array $data = [];

    /**
     * The current position in the content.
     */
    private int $index = 0;

    /**
     * The length of the content.
     */
    private int $length = 0;

    /**
     * The current file path.
     */
    private string $path = '';

    /**
     * Registers this class as the 'php' stream wrapper.
     *
     * @psalm-suppress UnusedFunctionCall
     */
    public static function register(): bool
    {
        $wrappers = \stream_get_wrappers();
        if (\in_array(self::PROTOCOL, $wrappers, true)) {
            \stream_wrapper_unregister(self::PROTOCOL);
        }

        return \stream_wrapper_register(self::PROTOCOL, self::class);
    }

    /**
     * Removes this class as the registered stream wrapper for 'php'.
     */
    public static function restore(): bool
    {
        return \stream_wrapper_restore(self::PROTOCOL);
    }

    public function stream_close(): void
    {
        if ('' === $this->content || '' === $this->path) {
            return;
        }

        self::$data[$this->path] = $this->content;
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

        if (isset(self::$data[$path])) {
            $this->content = self::$data[$path];
            $this->index = 0;
            $this->length = \strlen($this->content);
        }

        $this->path = $path;

        return true;
    }

    public function stream_read(int $count): string
    {
        if ('' === $this->content) {
            return '';
        }

        $length = \min($count, $this->length - $this->index);
        $data = \substr($this->content, $this->index, $length);
        $this->index += $length;

        return $data;
    }

    public function stream_seek(): bool
    {
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
        $length = \strlen($data);
        if ($length > 0) {
            $this->content .= $data;
            $this->length += $length;
        }

        return $length;
    }
}
