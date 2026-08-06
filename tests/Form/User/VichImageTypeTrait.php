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
 * @phpstan-require-extends TestCase
 */
trait VichImageTypeTrait
{
    protected function createVichImageType(): VichImageType
    {
        $callback = static fn (?User $user): ?string => $user?->getImageName();
        $storage = self::createStub(StorageInterface::class);
        $storage->method('resolveUri')
            ->willReturnCallback($callback);
        $handler = $this->createUploadHandler($storage);
        $factory = $this->createPropertyMappingFactory();

        return new VichImageType($storage, $handler, $factory);
    }

    private function createPropertyMappingFactory(): PropertyMappingFactory
    {
        $factory = self::createStub(AdvancedMetadataFactoryInterface::class);
        $metadata = new MetadataReader($factory);
        $resolver = self::createStub(PropertyMappingResolverInterface::class);

        return new PropertyMappingFactory($metadata, $resolver);
    }

    private function createUploadHandler(StorageInterface $storage): UploadHandler
    {
        $factory = $this->createPropertyMappingFactory();
        $injector = self::createStub(FileInjectorInterface::class);
        $dispatcher = self::createStub(EventDispatcherInterface::class);

        return new UploadHandler($factory, $storage, $injector, $dispatcher);
    }
}
