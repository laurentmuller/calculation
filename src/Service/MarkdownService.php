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
     * Process the given file.
     *
     * @param string $path        the file to process
     * @param array  $tags        the tags to update
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
        $content = $this->convertContent($content);
        if ($removeTitle) {
            $content = $this->removeTitle($content);
        }
        if ([] !== $tags) {
            return $this->updateTags($tags, $content);
        }

        return $content;
    }

    /**
     * Add the given class to the given tag.
     *
     * @param string $tag     the tag name to add class to
     * @param string $class   the class name to add
     * @param string $content the HTML content to update
     *
     * @return string the updated content
     */
    private function addTagClass(string $tag, string $class, string $content): string
    {
        $search = \sprintf('<%s>', $tag);
        $replace = \sprintf('<%s class="%s">', $tag, $class);

        return \str_replace($search, $replace, $content);
    }

    /**
     * Convert the given content to HTML.
     *
     * @param string $content the Markdown content to convert
     *
     * @return string the content converted to HTML
     */
    private function convertContent(string $content): string
    {
        $content = $this->markdown->convert($content);

        // remove line breaks of continuous texts
        return StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
    }

    /**
     * Remove the title (H1 tag) from the given content.
     *
     * @param string $content the HTML content to update
     * @param int    $limit   the maximum possible replacements or -1 to remove all
     *
     * @return string the updated content
     */
    private function removeTitle(string $content, int $limit = -1): string
    {
        return \trim(StringUtils::pregReplace('/<h1[^>]*>.*?<\/h1>/', '', $content, $limit));
    }

    /**
     * Replace the given old tag with the given new tag.
     *
     * @param string $oldTag  the tag name to search for
     * @param string $newTag  the tag name to replace by
     * @param string $content the HTML content to update
     *
     * @return string the updated content
     */
    private function replaceTag(string $oldTag, string $newTag, string $content): string
    {
        $pattern = \sprintf('/<(\/?)%s>/m', $oldTag);
        $replacement = \sprintf('<$1%s>', $newTag);

        return StringUtils::pregReplace($pattern, $replacement, $content);
    }

    /**
     * Update the given HTML content with the given tags.
     *
     * Each tag entry contains the old tag name, the new tag name and optionally a class name to add.
     *
     * @param array  $tags    the tags
     * @param string $content the HTML content to update
     *
     * @phpstan-param TagType[] $tags
     *
     * @return string the updated content
     */
    private function updateTags(array $tags, string $content): string
    {
        foreach ($tags as $tag) {
            if ($tag[0] !== $tag[1]) {
                $content = $this->replaceTag($tag[0], $tag[1], $content);
            }
            if (isset($tag[2])) {
                $content = $this->addTagClass($tag[1], $tag[2], $content);
            }
        }

        return $content;
    }
}
