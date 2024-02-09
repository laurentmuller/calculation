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

namespace App\Twig;

use App\Utils\StringUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for preg_** functions.
 */
class PregExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('preg_filter', $this->pregFilter(...)),
             new TwigFilter('preg_grep', $this->pregGrep(...)),
             new TwigFilter('preg_match', $this->pregMatch(...)),
             new TwigFilter('preg_quote', $this->pregQuote(...)),
             new TwigFilter('preg_replace', $this->pregReplace(...)),
             new TwigFilter('preg_split', $this->pregSplit(...)),
        ];
    }

    /**
     * Perform a regular expression search and replace, returning only matched subjects.
     *
     * @param string|string[]|null $subject
     * @param string|string[]      $pattern
     * @param string|string[]      $replacement
     *
     * @return string|string[]|null
     *
     * @psalm-param array<array-key, non-empty-string>|non-empty-string $pattern
     */
    protected function pregFilter(string|array|null $subject, string|array $pattern, string|array $replacement, int $limit = -1): string|array|null
    {
        if (null === $subject) {
            return null;
        }

        return \preg_filter($pattern, $replacement, $subject, $limit);
    }

    /**
     * Perform a regular expression match and return an array of entries that match the pattern.
     *
     * @param string[]|null $subject
     *
     * @psalm-param int<0,1> $flags
     * @psalm-param non-empty-string $pattern
     */
    protected function pregGrep(array|null $subject, string $pattern, int $flags = 0): array|false
    {
        if (null === $subject) {
            return false;
        }

        return \preg_grep($pattern, $subject, $flags);
    }

    /**
     * Perform a regular expression match.
     *
     * @psalm-param  int-mask<256, 512, 768> $flags
     * @psalm-param  int                     $offset
     * @psalm-param non-empty-string $pattern
     */
    protected function pregMatch(?string $subject, string $pattern, int $flags = 0, int $offset = 0): array|false
    {
        if (!StringUtils::isString($subject)) {
            return false;
        }
        $matches = [];
        if (1 === \preg_match($pattern, $subject, $matches, $flags, $offset)) {
            return $matches;
        }

        return false;
    }

    /**
     * Quote regular expression characters.
     */
    protected function pregQuote(?string $subject, ?string $delimiter = null): ?string
    {
        if (null === $subject) {
            return null;
        }

        return \preg_quote($subject, $delimiter);
    }

    /**
     * Perform a regular expression search and replace.
     *
     * @param string[]|string|null $subject
     * @param string[]|string      $pattern
     * @param string[]|string      $replacement
     *
     * @return string[]|string|null
     *
     * @psalm-param array<array-key, non-empty-string>|non-empty-string $pattern
     */
    protected function pregReplace(array|string|null $subject, array|string $pattern, array|string $replacement, int $limit = -1): array|string|null
    {
        if (null === $subject) {
            return null;
        }

        return \preg_replace($pattern, $replacement, $subject, $limit);
    }

    /**
     * Split text into an array using a regular expression.
     *
     * @return string[]|false
     *
     * @psalm-param non-empty-string $pattern
     */
    protected function pregSplit(?string $subject, string $pattern): array|false
    {
        if (null === $subject) {
            return false;
        }

        return \preg_split($pattern, $subject);
    }
}
