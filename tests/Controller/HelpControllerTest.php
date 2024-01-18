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
use App\Utils\FileUtils;

#[\PHPUnit\Framework\Attributes\CoversClass(HelpController::class)]
class HelpControllerTest extends AbstractControllerTestCase
{
    private const IMAGES_PATH = 'public/help/images';

    private HelpService $help;

    protected function setUp(): void
    {
        parent::setUp();
        $this->help = $this->getService(HelpService::class);
    }

    public static function getRoutes(): array
    {
        return [
            ['/help/', self::ROLE_USER],
            ['/help/', self::ROLE_ADMIN],
            ['/help/', self::ROLE_SUPER_ADMIN],

            ['/help/pdf', self::ROLE_USER],
            ['/help/pdf', self::ROLE_ADMIN],
            ['/help/pdf', self::ROLE_SUPER_ADMIN],

            ['/help/entity/product', self::ROLE_USER],
            ['/help/entity/product', self::ROLE_ADMIN],
            ['/help/entity/product', self::ROLE_SUPER_ADMIN],

            ['/help/dialog/product.list.title', self::ROLE_USER],
            ['/help/dialog/product.list.title', self::ROLE_ADMIN],
            ['/help/dialog/product.list.title', self::ROLE_SUPER_ADMIN],
        ];
    }

    public function testDialogs(): void
    {
        $dialogs = $this->help->getDialogs();
        foreach ($dialogs as $dialog) {
            $url = \sprintf('/help/dialog/%s', $dialog['id']);
            $this->checkUrl($url);
        }
    }

    public function testDialogsImages(): void
    {
        $extension = HelpService::IMAGES_EXT;
        $projectDir = $this->client->getKernel()->getProjectDir();
        $dialogs = $this->help->getDialogs();

        foreach ($dialogs as $dialog) {
            if (isset($dialog['image'])) {
                $this->checkImage($projectDir, $dialog['image'], $extension);
            }
            if (isset($dialog['images'])) {
                foreach ($dialog['images'] as $image) {
                    $this->checkImage($projectDir, $image, $extension);
                }
            }
            if (isset($dialog['forbidden']['image'])) {
                $this->checkImage($projectDir, $dialog['forbidden']['image'], $extension);
            }
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

    private function checkImage(string $projectDir, string $path, string $extension): void
    {
        $name = $path . $extension;
        $filename = FileUtils::buildPath($projectDir, self::IMAGES_PATH, $name);
        self::assertFileExists($filename);
    }

    private function checkUrl(string $url): void
    {
        $this->checkRoute($url, self::ROLE_USER);
        $this->checkRoute($url, self::ROLE_ADMIN);
        $this->checkRoute($url, self::ROLE_SUPER_ADMIN);
    }
}
