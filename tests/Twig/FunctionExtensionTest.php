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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class FunctionExtensionTest extends IntegrationTestCase implements RuntimeLoaderInterface
{
    private FunctionExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $webDir = __DIR__ . '/../../public';
        $assetExtension = $this->createAssetExtension();
        $webLinkExtension = $this->createWebLinkExtension();
        $nonceService = $this->createNonceService();
        $uploaderHelper = $this->createUploaderHelper();
        $urlGeneratorService = $this->createUrlGeneratorService();

        $this->extension = new FunctionExtension(
            $webDir,
            $assetExtension,
            $webLinkExtension,
            $nonceService,
            $uploaderHelper,
            $urlGeneratorService
        );
    }

    #[\Override]
    public function load(string $class): ?object
    {
        if (FunctionExtension::class === $class) {
            return $this->extension;
        }

        return null;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [new AttributeExtension(FunctionExtension::class)];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FunctionExtension';
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }

    private function createAssetExtension(): AssetExtension
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')
            ->willReturn('url');

        return new AssetExtension($packages);
    }

    private function createNonceService(): MockObject&NonceService
    {
        $service = $this->createMock(NonceService::class);
        $service->method('getCspNonce')
            ->willReturn('nonce');
        $service->method('getNonce')
            ->willReturn('nonce');

        return $service;
    }

    private function createUploaderHelper(): UploaderHelper
    {
        $callback = fn (object|array|null $value): mixed => null !== $value ? ((array) $value)[0] : null;
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUri')
            ->willReturnCallback($callback);

        return new UploaderHelper($storage);
    }

    private function createUrlGeneratorService(): MockObject&UrlGeneratorService
    {
        $generator = $this->createMock(UrlGeneratorService::class);
        $generator->method('routeParams')
            ->willReturn([]);
        $generator->method('cancelUrl')
            ->willReturn('cancelUrl');

        return $generator;
    }

    private function createWebLinkExtension(): WebLinkExtension
    {
        $stack = $this->createMock(RequestStack::class);

        return new WebLinkExtension($stack);
    }
}
