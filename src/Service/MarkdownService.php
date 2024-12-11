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
     * @param non-empty-string $tag
     * @param non-empty-string $class
     */
    public function addTagClass(string $tag, string $class, string $content): string
    {
        $search = \sprintf('<%s>', $tag);
        $replace = \sprintf('<%s class="%s">', $tag, $class);

        return \str_replace($search, $replace, $content);
    }

    /**
     * Convert the given content to HTML.
     *
     * @param string $content     the content to convert
     * @param bool   $removeTitle true to remove H1 tags
     */
    public function convertContent(string $content, bool $removeTitle = false): string
    {
        $content = $this->markdown->convert($content);
        if ($removeTitle) {
            $content = \trim(StringUtils::pregReplace('/<h1[^>]*>.*?<\/h1>/', '', $content));
        }

        return StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
    }

    /**
     * Convert the content of the given file to HTML.
     *
     * @param string $path        the file to load and to convert
     * @param bool   $removeTitle true to remove H1 tags
     */
    public function convertFile(string $path, bool $removeTitle = false): string
    {
        return $this->convertContent(FileUtils::readFile($path), $removeTitle);
    }

    /**
     * @param non-empty-string $oldTag
     * @param non-empty-string $newTag
     */
    public function replaceTag(string $oldTag, string $newTag, string $content): string
    {
        $pattern = \sprintf('/<(\/?)%s>/m', $oldTag);
        $replacement = \sprintf('<$1%s>', $newTag);

        return StringUtils::pregReplace($pattern, $replacement, $content);
    }

    /**
     * @param non-empty-string $oldTag
     * @param non-empty-string $newTag
     * @param non-empty-string $class
     */
    public function updateTag(string $oldTag, string $newTag, string $class, string $content): string
    {
        $content = $this->replaceTag($oldTag, $newTag, $content);

        return $this->addTagClass($newTag, $class, $content);
    }
}
