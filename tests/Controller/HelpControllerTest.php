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

namespace App\Tests\Controller;

use App\Service\HelpService;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HelpControllerTest extends ControllerTestCase
{
    private const IMAGES_PATH = 'public/help/images';

    private HelpService $help;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->help = $this->getService(HelpService::class);
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/help',
            '/help/dialogs',
            '/help/dialog/index.title',
            '/help/entities',
            '/help/entity/category',
            '/help/pdf',
            '/help/pdf/entity/log',
            '/help/pdf/dialog/user.rights.title',
        ];

        foreach ($routes as $route) {
            yield [$route, self::ROLE_USER];
        }
    }

    public function testDialogNotFound(): void
    {
        $this->checkRoute('/help/dialog/fake_dialog_fake', self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }

    public function testDialogs(): void
    {
        $dialogs = $this->help->getDialogs();
        foreach ($dialogs as $dialog) {
            $url = \sprintf('/help/dialog/%s', $dialog['id']);
            $this->checkUrl($url);
        }
    }

    public function testDownloadInvalidImage(): void
    {
        $parameters = [
            'index' => 0,
            'location' => 'Fake',
            'image' => 'Fake',
        ];
        $this->checkRoute(
            '/help/download',
            self::ROLE_SUPER_ADMIN,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testDownloadInvalidLocation(): void
    {
        $parameters = [
            'index' => 0,
            'location' => '//',
            'image' => $this->getDownloadImage(),
        ];
        $this->checkRoute(
            '/help/download',
            self::ROLE_SUPER_ADMIN,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testDownloadWithImage(): void
    {
        $parameters = [
            'index' => 0,
            'location' => 'example',
            'image' => $this->getDownloadImage(),
        ];
        $this->checkRoute(
            '/help/download',
            self::ROLE_SUPER_ADMIN,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testEntities(): void
    {
        $entities = $this->help->getEntities();
        foreach ($entities as $entity) {
            $url = \sprintf('/help/entity/%s', $entity['id']);
            $this->checkUrl($url);
        }
    }

    public function testEntityNotFound(): void
    {
        $this->checkRoute('/help/entity/fake_entity_fake', self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }

    public function testImages(): void
    {
        foreach ($this->getImages() as $file) {
            self::assertFileExists($file);
        }
    }

    public function testPdfDialogNotFound(): void
    {
        $this->checkRoute('/help/pdf/dialog/fake_dialog_fake', self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }

    public function testPdfEntityNotFound(): void
    {
        $this->checkRoute('/help/pdf/entity/fake_entity_fake', self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }

    public function testUnusedImages(): void
    {
        $expected = \iterator_to_array($this->getImages());
        \sort($expected);

        $actual = $this->getExistingImages();
        \sort($actual);

        $diff = \array_diff($actual, $expected);
        if ([] !== $diff) {
            $diff = \array_map(static fn (string $file): string => \basename($file), $diff);
            self::markTestSkipped("Not all images have been implemented in help:\n" . \implode("\n", $diff));
        }

        self::assertSame($actual, $expected);
    }

    private function checkUrl(string $url): void
    {
        $this->checkRoute($url, self::ROLE_USER);
    }

    private function getDownloadImage(): string
    {
        $path = __DIR__ . '/../files/images/example.png';
        $type = (string) \mime_content_type($path);
        $data = (string) \file_get_contents($path);
        $encoded = \base64_encode($data);

        return 'data:' . $type . ';base64,' . $encoded;
    }

    private function getExistingImages(): array
    {
        $name = '*' . HelpService::IMAGES_EXT;
        $projectDir = $this->client->getKernel()->getProjectDir();
        $dir = Path::join($projectDir, self::IMAGES_PATH);
        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->name($name);
        $files = [];
        foreach ($finder as $file) {
            $files[] = Path::canonicalize($file->getRealPath());
        }

        return $files;
    }

    private function getImagePath(string $projectDir, string $path, string $extension): string
    {
        $file_name = $path . $extension;
        $full_path = Path::join($projectDir, self::IMAGES_PATH, $file_name);

        return Path::canonicalize($full_path);
    }

    /**
     * @phpstan-return \Generator<string>
     */
    private function getImages(): \Generator
    {
        $extension = HelpService::IMAGES_EXT;
        $projectDir = $this->client->getKernel()->getProjectDir();
        $dialogs = $this->help->getDialogs();

        foreach ($dialogs as $dialog) {
            if (isset($dialog['image'])) {
                yield $this->getImagePath($projectDir, $dialog['image'], $extension);
            }
            if (isset($dialog['images'])) {
                foreach ($dialog['images'] as $image) {
                    yield $this->getImagePath($projectDir, $image, $extension);
                }
            }
            if (isset($dialog['forbidden']['image'])) {
                yield $this->getImagePath($projectDir, $dialog['forbidden']['image'], $extension);
            }
        }

        $menu = $this->help->getMainMenu();
        if (isset($menu['image'])) {
            yield $this->getImagePath($projectDir, $menu['image'], $extension);
        }
    }
}
