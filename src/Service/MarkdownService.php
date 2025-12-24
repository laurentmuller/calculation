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
 * Service to convert Markdown to HTML and to update tags.
 *
 * @phpstan-type TagType = array{0: string, 1: string, 2?: string}
 */
readonly class MarkdownService
{
    public function __construct(private MarkdownInterface $markdown)
    {
    }

    /**
     * Process the given Markdown file path.
     *
     * Each tag entry contains the old tag name, the new tag name and optionally a class name to add.
     *
     * @param string $path        the file path to process
     * @param array  $tags        the HTML tags to update
     * @param bool   $removeTitle true to remove the title (H1 tags)
     *
     * @phpstan-param TagType[] $tags
     */
    public function processFile(string $path, array $tags = [], bool $removeTitle = true): string
    {
        $content = FileUtils::readFile($path);
        if ('' === $content) {
            return $content;
        }
        $content = $this->convert($content);
        if ($removeTitle) {
            $content = $this->removeTitle($content);
        }
        if ([] !== $tags) {
            return $this->updateTags($tags, $content);
        }

        return $content;
    }

    /**
     * Convert the given Markdown content to HTML.
     */
    private function convert(string $content): string
    {
        $content = $this->markdown->convert($content);

        // remove line breaks of continuous texts
        return StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
    }

    /**
     * Remove the title (H1 tag) from the given HTML content.
     */
    private function removeTitle(string $content): string
    {
        return \trim(StringUtils::pregReplace('/<h1[^>]*>.*?<\/h1>/', '', $content));
    }

    /**
     * Update the given HTML content with the given tags.
     *
     * @phpstan-param TagType[] $tags
     */
    private function updateTags(array $tags, string $content): string
    {
        foreach ($tags as $tag) {
            if ($tag[0] !== $tag[1]) {
                $pattern = \sprintf('/<(\/?)%s>/m', $tag[0]);
                $replacement = \sprintf('<$1%s>', $tag[1]);
                $content = StringUtils::pregReplace($pattern, $replacement, $content);
            }
            if (isset($tag[2])) {
                $search = \sprintf('<%s>', $tag[1]);
                $replace = \sprintf('<%s class="%s">', $tag[1], $tag[2]);
                $content = \str_replace($search, $replace, $content);
            }
        }

        return $content;
    }
}
