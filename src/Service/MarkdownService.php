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

namespace App\Service;

use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Service to convert mark down to HTML.
 */
readonly class MarkdownService
{
    public function __construct(private MarkdownInterface $markdown)
    {
    }

    /**
     * Convert the given content to HTML.
     *
     * @param string                $content     the content to convert
     * @param bool                  $removeTitle true to remove H1 tags
     * @param array<string, string> $replaces    the values to replace
     */
    public function convertContent(string $content, bool $removeTitle = false, array $replaces = []): string
    {
        $content = $this->markdown->convert($content);
        if ($removeTitle) {
            $content = \trim(StringUtils::pregReplace('/<h1[^>]*>.*?<\/h1>/', '', $content));
        }

        return [] === $replaces ? $content : StringUtils::replace($replaces, $content);
    }

    /**
     * Convert the content of the given file to HTML.
     *
     * @param string                $path        the file to load and to convert
     * @param bool                  $removeTitle true to remove H1 tags
     * @param array<string, string> $replaces    the values to replace
     */
    public function convertFile(string $path, bool $removeTitle = false, array $replaces = []): string
    {
        return $this->convertContent(FileUtils::readFile($path), $removeTitle, $replaces);
    }
}
