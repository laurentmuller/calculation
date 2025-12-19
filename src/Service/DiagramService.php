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

use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get Mermaid diagrams.
 *
 * @see https://mermaid.js.org/
 *
 * @phpstan-type DiagramType = array{
 *     name: string,
 *     title: string,
 *     content: string}
 */
class DiagramService implements \Countable
{
    private const FILE_EXTENSION = '.mmd';
    private const TITLE_PATTERN = '/title\s?:\s?(.*)/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/diagrams/')]
        private readonly string $path,
        private readonly CacheInterface $cache
    ) {
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getDiagrams());
    }

    /**
     * Gets the diagram for the given name.
     *
     * @phpstan-return DiagramType
     *
     * @throws \InvalidArgumentException if the diagram name does not exist
     */
    public function getDiagram(string $name): array
    {
        return $this->getDiagrams()[$name] ?? throw new \InvalidArgumentException(\sprintf('Unknown diagram name: "%s".', $name));
    }

    /**
     * Gets all diagrams.
     *
     * @phpstan-return array<string, DiagramType>
     */
    public function getDiagrams(): array
    {
        return $this->cache->get('diagram_files', $this->loadDiagrams(...));
    }

    /**
     * Gets a value indicating if the diagram exists for the given name.
     */
    public function hasDiagram(string $name): bool
    {
        return \array_key_exists($name, $this->getDiagrams());
    }

    private function createFinder(): Finder
    {
        return Finder::create()
            ->in($this->path)
            ->files()
            ->name('*' . self::FILE_EXTENSION);
    }

    private function findTitle(string $content, string $name): string
    {
        if (StringUtils::pregMatch(self::TITLE_PATTERN, $content, $matches)) {
            return $matches[1];
        }

        return StringUtils::unicode($name)
            ->replace('_', ' ')
            ->lower()
            ->title(allWords: true)
            ->toString();
    }

    /**
     * @phpstan-return array<string, DiagramType>
     */
    private function loadDiagrams(): array
    {
        $files = [];
        $finder = $this->createFinder();
        foreach ($finder as $file) {
            $content = $file->getContents();
            $name = $file->getBasename(self::FILE_EXTENSION);
            $title = $this->findTitle($content, $name);
            $content = $this->removeTitle($content);
            $files[$name] = [
                'name' => $name,
                'title' => $title,
                'content' => $content,
            ];
        }
        \uasort($files, static fn (array $a, array $b): int => $a['title'] <=> $b['title']);

        return $files;
    }

    private function removeTitle(string $content): string
    {
        $pos = \strrpos($content, '---');
        if (false !== $pos) {
            $content = \substr($content, $pos + 3);
        }

        return \trim($content);
    }
}
