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

namespace App\Tests\Form\User;

use App\Entity\User;
use Metadata\AdvancedMetadataFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Injector\FileInjectorInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Mapping\PropertyMappingResolverInterface;
use Vich\UploaderBundle\Metadata\MetadataReader;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * @psalm-require-extends TestCase
 */
trait VichImageTypeTrait
{
    protected function createVichImageType(): VichImageType
    {
        $callback = fn (?User $user): ?string => $user?->getImageName();
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUri')
            ->willReturnCallback($callback);
        $handler = $this->createUploadHandler($storage);

        return new VichImageType(
            $storage,
            $handler,
            $this->createPropertyMappingFactory(),
            null,
            null
        );
    }

    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    private function createPropertyMappingFactory(): PropertyMappingFactory
    {
        $factory = $this->createMock(AdvancedMetadataFactoryInterface::class);
        $metadata = new MetadataReader($factory);
        $resolver = $this->createMock(PropertyMappingResolverInterface::class);

        return new PropertyMappingFactory($metadata, $resolver);
    }

    private function createUploadHandler(MockObject&StorageInterface $storage): UploadHandler
    {
        $factory = $this->createPropertyMappingFactory();
        $injector = $this->createMock(FileInjectorInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        return new UploadHandler($factory, $storage, $injector, $dispatcher);
    }
}
