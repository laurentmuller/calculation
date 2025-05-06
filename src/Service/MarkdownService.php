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
 * @phpstan-type TagType = array{0: non-empty-string, 1: non-empty-string, 2?: non-empty-string}
 */
readonly class MarkdownService
{
    public function __construct(private MarkdownInterface $markdown)
    {
    }

    /**
     * Add the given class to the given tag.
     *
     * @param non-empty-string $tag     the tag name to add class to
     * @param non-empty-string $class   the class name to add
     * @param string           $content the HTML content to update
     *
     * @return string the updated content
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
     * @param string $content the Markdown content to convert
     *
     * @return string the content converted to HTML
     */
    public function convertContent(string $content): string
    {
        $content = $this->markdown->convert($content);

        // remove line breaks of continuous texts
        return StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
    }

    /**
     * Convert the Markdown content of the given file to HTML.
     *
     * @param string $path the file to load and to convert
     *
     * @return string the Markdown file content converted to HTML
     */
    public function convertFile(string $path): string
    {
        return $this->convertContent(FileUtils::readFile($path));
    }

    /**
     * Remove the title (H1 tag) from the given content.
     *
     * @param string $content the HTML content to update
     * @param int    $limit   the maximum possible replacements or -1 to remove all
     *
     * @return string the updated content
     */
    public function removeTitle(string $content, int $limit = -1): string
    {
        return \trim(StringUtils::pregReplace('/<h1[^>]*>.*?<\/h1>/', '', $content, $limit));
    }

    /**
     * Replace the given old tag with the given new tag.
     *
     * @param non-empty-string $oldTag  the tag name to search for
     * @param non-empty-string $newTag  the tag name to replace by
     * @param string           $content the HTML content to update
     *
     * @return string the updated content
     */
    public function replaceTag(string $oldTag, string $newTag, string $content): string
    {
        $pattern = \sprintf('/<(\/?)%s>/m', $oldTag);
        $replacement = \sprintf('<$1%s>', $newTag);

        return StringUtils::pregReplace($pattern, $replacement, $content);
    }

    /**
     * Update the given tag by replacing the given old tag with the given new tag and adding the given class.
     *
     * This is a combination of the <code>replaceTag()</code> and <code>addTagClass()</code> functions.
     *
     * @param non-empty-string $oldTag  the tag name to search for
     * @param non-empty-string $newTag  the tag name to replace by
     * @param non-empty-string $class   the class name to add
     * @param string           $content the HTML content to update
     *
     * @return string the updated content
     */
    public function updateTag(string $oldTag, string $newTag, string $class, string $content): string
    {
        $content = $this->replaceTag($oldTag, $newTag, $content);

        return $this->addTagClass($newTag, $class, $content);
    }

    /**
     * Update the given HTML content with the given tags.
     *
     * Each tag entry contains the old tag name, the new tag name and optionally a class name to add.
     *
     * @param array  $tags    the tags
     * @param string $content the HTML content to update
     *
     * @return string the updated content
     *
     * @phpstan-param TagType[] $tags
     */
    public function updateTags(array $tags, string $content): string
    {
        foreach ($tags as $tag) {
            $content = $this->replaceTag($tag[0], $tag[1], $content);
            if (isset($tag[2])) {
                $content = $this->addTagClass($tag[1], $tag[2], $content);
            }
        }

        return $content;
    }
}
