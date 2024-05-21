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

use App\Controller\HelpController;
use App\Service\HelpService;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[CoversClass(HelpController::class)]
class HelpControllerTest extends AbstractControllerTestCase
{
    private const IMAGES_PATH = 'public/help/images';

    private HelpService $help;

    protected function setUp(): void
    {
        parent::setUp();
        $this->help = $this->getService(HelpService::class);
    }

    public static function getRoutes(): \Iterator
    {
        yield ['/help', self::ROLE_USER];
        yield ['/help', self::ROLE_ADMIN];
        yield ['/help', self::ROLE_SUPER_ADMIN];
        yield ['/help/pdf', self::ROLE_USER];
        yield ['/help/pdf', self::ROLE_ADMIN];
        yield ['/help/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/help/entity/product', self::ROLE_USER];
        yield ['/help/entity/product', self::ROLE_ADMIN];
        yield ['/help/entity/product', self::ROLE_SUPER_ADMIN];
        yield ['/help/dialog/product.list.title', self::ROLE_USER];
        yield ['/help/dialog/product.list.title', self::ROLE_ADMIN];
        yield ['/help/dialog/product.list.title', self::ROLE_SUPER_ADMIN];
    }

    public function testDialogs(): void
    {
        $dialogs = $this->help->getDialogs();
        foreach ($dialogs as $dialog) {
            $url = \sprintf('/help/dialog/%s', $dialog['id']);
            $this->checkUrl($url);
        }
    }

    public function testEntities(): void
    {
        $entities = $this->help->getEntities();
        foreach ($entities as $entity) {
            $url = \sprintf('/help/entity/%s', $entity['id']);
            $this->checkUrl($url);
        }
    }

    public function testImages(): void
    {
        foreach ($this->getImages() as $file) {
            self::assertFileExists($file);
        }
    }

    public function testUnusedImages(): void
    {
        $expected = \iterator_to_array($this->getImages());
        \sort($expected);

        $actual = $this->getExistingImages();
        \sort($actual);

        $diff = \array_diff($actual, $expected);
        if ([] !== $diff) {
            $diff = \array_map(fn (string $file): string => \basename($file), $diff);
            self::markTestSkipped("Not all images have been implemented in help:\n" . \implode("\n", $diff));
        }

        self::assertSame($actual, $expected);
    }

    private function checkUrl(string $url): void
    {
        $this->checkRoute($url, self::ROLE_USER);
        $this->checkRoute($url, self::ROLE_ADMIN);
        $this->checkRoute($url, self::ROLE_SUPER_ADMIN);
    }

    private function getExistingImages(): array
    {
        $name = '*' . HelpService::IMAGES_EXT;
        $projectDir = $this->client->getKernel()->getProjectDir();
        $dir = Path::canonicalize(Path::join($projectDir, self::IMAGES_PATH));
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
     * @return \Generator<string>
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
