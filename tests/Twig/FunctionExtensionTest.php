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

namespace App\Tests\Twig;

use App\Service\NonceService;
use App\Service\UrlGeneratorService;
use App\Twig\FunctionExtension;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Packages;
use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class FunctionExtensionTest extends IntegrationTestCase
{
    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        $webDir = __DIR__ . '/../../public';
        $extension = $this->createAssetExtension();
        $service = $this->createNonceService();
        $helper = $this->createHelper();
        $generator = $this->createGenerator();

        $testExtension = new FunctionExtension(
            $webDir,
            $extension,
            $service,
            $helper,
            $generator
        );

        return [$testExtension];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FunctionExtension';
    }

    /**
     * @throws Exception
     */
    private function createAssetExtension(): AssetExtension
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')
            ->willReturn('url');

        return new AssetExtension($packages);
    }

    /**
     * @throws Exception
     */
    private function createGenerator(): MockObject&UrlGeneratorService
    {
        $generator = $this->createMock(UrlGeneratorService::class);
        $generator->method('routeParams')
            ->willReturn([]);
        $generator->method('cancelUrl')
            ->willReturn('cancelUrl');

        return $generator;
    }

    /**
     * @throws Exception
     */
    private function createHelper(): UploaderHelper
    {
        $callback = fn (?array $value): mixed => \is_array($value) ? $value[0] : $value;
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUri')
            ->willReturnCallback($callback);

        return new UploaderHelper($storage);
    }

    /**
     * @throws Exception
     */
    private function createNonceService(): MockObject&NonceService
    {
        $service = $this->createMock(NonceService::class);
        $service->method('getCspNonce')
            ->willReturn('nonce');
        $service->method('getNonce')
            ->willReturn('nonce');

        return $service;
    }
}
