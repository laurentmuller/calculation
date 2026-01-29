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
use App\Twig\FunctionExtension;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @extends RuntimeTestCase<FunctionExtension>
 */
final class FunctionExtensionTest extends RuntimeTestCase
{
    #[\Override]
    protected function createService(): FunctionExtension
    {
        return new FunctionExtension(
            $this->getPublicDir(),
            $this->createAssetExtension(),
            $this->createWebLinkExtension(),
            $this->createNonceService(),
            $this->createUploaderHelper(),
        );
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FunctionExtension';
    }

    private function createAssetExtension(): AssetExtension
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')
            ->willReturn('url');

        return new AssetExtension($packages);
    }

    private function createNonceService(): NonceService
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
        $callback = static fn (mixed $value): mixed => \is_array($value) ? $value[0] : null;
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUri')
            ->willReturnCallback($callback);

        return new UploaderHelper($storage);
    }

    private function createWebLinkExtension(): WebLinkExtension
    {
        return new WebLinkExtension(self::createStub(RequestStack::class));
    }

    private function getPublicDir(): string
    {
        return __DIR__ . '/../../public';
    }
}
