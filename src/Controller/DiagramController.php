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

namespace App\Controller;

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display Mermaid diagrams.
 */
#[AsController]
#[Route(path: '/test')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class DiagramController extends AbstractController
{
    private const DIAGRAM_SUFFIX = '.mmd';
    private const TITLE_PATTERN = '/title\s?:\s?(.*)/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/diagram/')]
        private readonly string $root,
    ) {
    }

    /**
     * Display a diagram.
     */
    #[Get(path: '/diagram', name: 'test_diagram')]
    public function diagram(
        #[MapQueryParameter]
        string $name = 'overall_diagram'
    ): Response {
        $content = $this->loadContent($name);
        if (false === $content) {
            $content = <<<DIAGRAM
                    ---
                    title: Empty
                    ---
                    classDiagram
                        class EmptyClass {
                        }
                DIAGRAM;
        }

        $title = $this->getTitle($content) ?? 'Diagram';
        $content = $this->removeTitle($content);
        $files = $this->getFiles();

        return $this->render('test/diagram.html.twig', [
            'diagram_name' => $name,
            'diagram_title' => $title,
            'diagram_content' => $content,
            'diagram_files' => $files,
        ]);
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getFiles(): array
    {
        $files = [];
        $finder = new Finder();
        $finder->in($this->root)
            ->files()
            ->name('*.mmd');
        foreach ($finder as $file) {
            $content = $file->getContents();
            $title = $this->getTitle($content) ?? 'Diagram';
            $files[$file->getBasename(self::DIAGRAM_SUFFIX)] = $title;
        }
        \asort($files);

        return $files;
    }

    private function getTitle(string $content): ?string
    {
        $matches = [];
        if (false !== \preg_match_all(self::TITLE_PATTERN, $content, $matches, \PREG_SET_ORDER)) {
            return $matches[0][1];
        }

        return null;
    }

    private function loadContent(string $name): string|false
    {
        $file = FileUtils::buildPath($this->root, $name . self::DIAGRAM_SUFFIX);
        if (!\file_exists($file)) {
            return false;
        }

        return \file_get_contents($file);
    }

    private function removeTitle(string $content): string
    {
        return (string) \preg_replace(self::TITLE_PATTERN, '', $content);
    }
}
