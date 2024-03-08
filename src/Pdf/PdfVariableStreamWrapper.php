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

namespace App\Pdf;

/**
 * Stream wrapper to read content from a global variable.
 *
 * @internal
 */
final class PdfVariableStreamWrapper
{
    /**
     * The resource context.
     *
     * @var resource|null
     */
    public mixed $context = null;

    private int $position = 0;
    private bool $reportErrors = false;
    private string $variable = '';

    /**
     * Return if the end of the global variable position is reached.
     *
     * @return bool <code>true</code> if the end is reached, <code>false</code> otherwise
     */
    public function stream_eof(): bool
    {
        if (!$this->isVariable()) {
            return true;
        }

        return $this->position >= \strlen((string) $GLOBALS[$this->variable]);
    }

    /**
     * Opens stream for reading a global variable.
     *
     * @param string $path    specifies the URL that was passed to the original function
     * @param string $mode    the open mode
     * @param int    $options additional flags set by the streams API
     *
     * @return bool <code>true</code> on success or <code>false</code> on failure
     */
    public function stream_open(string $path, string $mode, int $options): bool
    {
        $this->reportErrors = ($options & \STREAM_REPORT_ERRORS) === \STREAM_REPORT_ERRORS;
        if (!\str_contains($mode, 'r')) {
            if ($this->reportErrors) {
                \trigger_error(\sprintf('Invalid mode "%s": only "r" or "rb" are supported.', $mode), \E_USER_WARNING);
            }

            return false;
        }

        $url = \parse_url($path);
        $this->variable = $url['host'] ?? '';
        if (!$this->isVariable()) {
            return false;
        }

        if (!isset($GLOBALS[$this->variable])) {
            if ($this->reportErrors) {
                \trigger_error(\sprintf('The global variable "%s" is not set.', $this->variable), \E_USER_WARNING);
            }

            return false;
        }
        $this->position = 0;

        return true;
    }

    /**
     * Read a string from the global variable.
     *
     * @param int $count how many bytes of data from the current position should be returned
     *
     * @return string the reading string or an empty string if no more data
     */
    public function stream_read(int $count): string
    {
        if (!$this->isVariable()) {
            return '';
        }

        $value = (string) $GLOBALS[$this->variable];
        $result = \substr($value, $this->position, $count);
        $this->position += \strlen($result);

        return $result;
    }

    /**
     * Seeks to specific location.
     *
     * @param int $offset the offset to seek to
     * @param int $whence how the position must be updated
     *
     * @return bool <code>true</code> if the position was updated, <code>false</code> otherwise
     */
    public function stream_seek(int $offset, int $whence = \SEEK_SET): bool
    {
        if (\SEEK_SET !== $whence) {
            return false;
        }

        $this->position = $offset;

        return true;
    }

    /**
     * Return information about the global variable.
     *
     * This implementation return an empty array.
     *
     * @return array<int, string> an empty array
     */
    public function stream_stat(): array
    {
        return [];
    }

    /**
     * Gets the current position in the global variable.
     *
     * @return int the current position
     */
    public function stream_tell(): int
    {
        return $this->position;
    }

    /**
     * @psalm-assert-if-true non-empty-string $this->variable
     */
    private function isVariable(): bool
    {
        if ('' !== $this->variable) {
            return true;
        }
        if ($this->reportErrors) {
            \trigger_error('The host variable is not defined.', \E_USER_WARNING);
        }

        return false;
    }
}
