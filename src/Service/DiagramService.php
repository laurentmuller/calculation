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

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get diagrams.
 *
 * @psalm-type DiagramType = array{
 *     title: string,
 *     content: string
 * }
 */
class DiagramService
{
    public const EMPTY_DIAGRAM = <<<DIAGRAM
            ---
            title: Empty
            ---
            classDiagram
                class EmptyClass {
                }
        DIAGRAM;

    private const FILE_SUFFIX = '.mmd';
    private const TITLE_PATTERN = '/title\s?:\s?(.*)/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/diagram/')]
        private readonly string $root,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the file title and content for the given name.
     *
     * @psalm-return DiagramType|null
     *
     * @throws InvalidArgumentException
     */
    public function getFile(string $name): ?array
    {
        $files = $this->getFiles();
        if (\array_key_exists($name, $files)) {
            return $files[$name];
        }

        return null;
    }

    /**
     * Gets all diagram files.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array<string, DiagramType>
     */
    public function getFiles(): array
    {
        return $this->cache->get('diagram_files', function (): array {
            $files = [];
            $finder = new Finder();
            $finder->in($this->root)
                ->files()
                ->name('*' . self::FILE_SUFFIX);
            foreach ($finder as $file) {
                $content = $file->getContents();
                $title = $this->findTitle($content) ?? 'Diagram';
                $content = $this->removeTitle($content);
                $files[$file->getBasename(self::FILE_SUFFIX)] = [
                    'title' => $title,
                    'content' => $content,
                ];
            }
            \uasort($files, fn (array $a, array $b): int => $a['title'] <=> $b['title']);

            return $files;
        });
    }

    /**
     * Gets the diagram title for the given content.
     *
     * @throws InvalidArgumentException
     */
    public function getTitle(string $name, string $default = 'Diagram'): ?string
    {
        $files = $this->getFiles();
        if (\array_key_exists($name, $files)) {
            return $files[$name]['title'];
        }

        return $default;
    }

    private function findTitle(string $content): ?string
    {
        $matches = [];
        if (false !== \preg_match_all(self::TITLE_PATTERN, $content, $matches, \PREG_SET_ORDER)) {
            return $matches[0][1];
        }

        return null;
    }

    private function removeTitle(string $content): string
    {
        return (string) \preg_replace(self::TITLE_PATTERN, '', $content);
    }
}
