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

use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Extends the integration test case with a service.
 *
 * @template TService of object
 */
abstract class RuntimeTestCase extends IntegrationTestCase implements RuntimeLoaderInterface
{
    /**
     * @var TService
     */
    private object $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = $this->createService();
    }

    /**
     * @return TService|null
     */
    #[\Override]
    public function load(string $class): ?object
    {
        return $this->getServiceClass() === $class ? $this->service : null;
    }

    /**
     * @return TService
     */
    abstract protected function createService(): object;

    #[\Override]
    protected function getExtensions(): array
    {
        return [new AttributeExtension($this->getServiceClass())];
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }

    /**
     * @return class-string<TService>
     */
    protected function getServiceClass(): string
    {
        return $this->service::class;
    }
}
