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

namespace App\Tests\Listener;

use App\Entity\User;
use App\Listener\VichListener;
use App\Service\ImageResizer;
use App\Service\UserNamer;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class VichListenerTest extends TestCase
{
    use TranslatorMockTrait;

    public function testPreUploadInvalidFile(): void
    {
        $event = $this->createEvent();
        $resizer = self::createMock(ImageResizer::class);
        $resizer->expects(self::never())
            ->method('resize');
        $listener = $this->createListener($resizer);
        $listener->onPreUpload($event);
    }

    public function testPreUploadValidFile(): void
    {
        $file = $this->createUploadedFile();
        $event = $this->createEvent($file);
        $resizer = self::createMock(ImageResizer::class);
        $resizer->expects(self::once())
            ->method('resize');
        $listener = $this->createListener($resizer);
        $listener->onPreUpload($event);
    }

    public function testPreUploadWithEmptyNamer(): void
    {
        $namer = new class extends UserNamer {
            #[\Override]
            public function name(object $object, PropertyMapping $mapping): string
            {
                return '';
            }
        };

        $file = $this->createUploadedFile();
        $event = $this->createEvent($file, $namer);
        $resizer = self::createMock(ImageResizer::class);
        $resizer->expects(self::never())
            ->method('resize');
        $listener = $this->createListener($resizer);
        $listener->onPreUpload($event);
    }

    private function createEvent(?UploadedFile $file = null, ?UserNamer $namer = null): Event
    {
        $user = $this->createUser($file);
        $mapping = $this->createPropertyMapping($namer);

        return new Event($user, $mapping);
    }

    private function createListener(?ImageResizer $resizer = null): VichListener
    {
        $resizer ??= self::createStub(ImageResizer::class);

        return new VichListener($resizer);
    }

    private function createPropertyMapping(?UserNamer $namer = null): PropertyMapping
    {
        $mapping = new PropertyMapping(
            filePropertyPath: 'imageFile',
            fileNamePropertyPath: 'imageName',
            propertyPaths: [
                'propertyName' => 'imageFile',
                'fileNameProperty' => 'imageName',
            ]
        );
        $mapping->setNamer($namer ?? new UserNamer());
        $mapping->setMapping([
            'uri_prefix' => '/images/users',
            'upload_destination' => __DIR__,
        ]);

        return $mapping;
    }

    private function createUploadedFile(): UploadedFile
    {
        $name = 'user_example.jpg';
        $path = $this->getImagesPath() . $name;

        return new UploadedFile($path, $name);
    }

    private function createUser(?UploadedFile $file = null): User
    {
        $file ??= new UploadedFile(path: 'fake', originalName: 'fake', error: -1);
        $user = new User();
        $user->setUsername('user_name')
            ->setImageName($file->getBasename())
            ->setImageFile($file);

        return $user;
    }

    private function getImagesPath(): string
    {
        return __DIR__ . '/../files/images/';
    }
}
