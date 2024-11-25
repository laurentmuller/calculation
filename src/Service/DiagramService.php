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
 * @psalm-type DiagramType = array{
 *     name: string,
 *     title: string,
 *     content: string}
 */
class DiagramService
{
    private const FILE_SUFFIX = '.mmd';
    private const TITLE_PATTERN = '/title\s?:\s?(.*)/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/diagrams/')]
        private readonly string $path,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the diagram file for the given name.
     *
     * @psalm-return DiagramType|null
     */
    public function getFile(string $name): ?array
    {
        return $this->getFiles()[$name] ?? null;
    }

    /**
     * Gets all diagram files.
     *
     * @psalm-return array<string, DiagramType>
     */
    public function getFiles(): array
    {
        return $this->cache->get('diagram_files', fn (): array => $this->loadDiagrams());
    }

    private function findTitle(string $content, string $name): string
    {
        if (StringUtils::pregMatch(self::TITLE_PATTERN, $content, $matches)) {
            return $matches[1];
        }

        return StringUtils::unicode($name)
            ->camel()
            ->title()
            ->toString();
    }

    /**
     * @psalm-return array<string, DiagramType>
     */
    private function loadDiagrams(): array
    {
        $files = [];
        $finder = Finder::create()
            ->in($this->path)
            ->files()
            ->name('*' . self::FILE_SUFFIX);
        foreach ($finder as $file) {
            $content = $file->getContents();
            $name = $file->getBasename(self::FILE_SUFFIX);
            $title = $this->findTitle($content, $name);
            $content = $this->removeTitle($content);
            $files[$name] = [
                'name' => $name,
                'title' => $title,
                'content' => $content,
            ];
        }
        \uasort($files, fn (array $a, array $b): int => $a['title'] <=> $b['title']);

        return $files;
    }

    private function removeTitle(string $content): string
    {
        return StringUtils::pregReplace(self::TITLE_PATTERN, '', $content);
    }
}
