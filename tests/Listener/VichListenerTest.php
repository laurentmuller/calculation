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
use App\Utils\FileUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class VichListenerTest extends TestCase
{
    use TranslatorMockTrait;

    public function testPostUploadInvalidFile(): void
    {
        $event = $this->createEvent();
        $listener = $this->createListener();
        $listener->onPostUpload($event);
        self::expectNotToPerformAssertions();
    }

    public function testPostUploadNewFile(): void
    {
        $path = $this->getImagesPath();

        try {
            $name = 'user_new_000000.jpg';
            FileUtils::copy($path . 'user_example.jpg', $path . $name, true);
            $file = $this->createUploadedFile($name);
            $event = $this->createEvent($file);
            $listener = $this->createListener();
            $listener->onPostUpload($event);
            self::expectNotToPerformAssertions();
        } finally {
            FileUtils::remove($path . 'USER_000000_192.jpg');
        }
    }

    public function testPostUploadValidFile(): void
    {
        $file = $this->createUploadedFile();
        $event = $this->createEvent($file);
        $listener = $this->createListener();
        $listener->onPostUpload($event);
        self::expectNotToPerformAssertions();
    }

    public function testPreRemove(): void
    {
        $event = $this->createEvent();
        $listener = $this->createListener();
        $listener->onPreRemove($event);
        self::expectNotToPerformAssertions();
    }

    public function testPreUploadInvalidFile(): void
    {
        $event = $this->createEvent();
        $listener = $this->createListener();
        $listener->onPreUpload($event);
        self::expectNotToPerformAssertions();
    }

    public function testPreUploadValidFile(): void
    {
        $file = $this->createUploadedFile();
        $event = $this->createEvent($file);
        $listener = $this->createListener();
        $listener->onPreUpload($event);
        self::expectNotToPerformAssertions();
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
        $listener = $this->createListener();
        $listener->onPreUpload($event);
        self::expectNotToPerformAssertions();
    }

    private function createEvent(
        ?UploadedFile $file = null,
        ?UserNamer $namer = null
    ): Event {
        $user = $this->createUser($file);
        $mapping = $this->createPropertyMapping($namer);

        return new Event($user, $mapping);
    }

    private function createListener(): VichListener
    {
        $resizer = $this->createMockImageResizer();

        return new VichListener($resizer);
    }

    private function createMockImageResizer(): MockObject&ImageResizer
    {
        $translator = $this->createMockTranslator();
        $logger = $this->createMock(LoggerInterface::class);
        $resizer = $this->createMock(ImageResizer::class);
        $resizer->setTranslator($translator);
        $resizer->setLogger($logger);

        return $resizer;
    }

    private function createPropertyMapping(?UserNamer $namer = null): PropertyMapping
    {
        $mapping = new PropertyMapping(
            'imageFile',
            'imageName',
            [
                'propertyName' => 'imageFile',
                'fileNameProperty' => 'imageName',
            ]
        );
        $mapping->setNamer($this->getNamer($namer));
        $mapping->setMapping([
            'uri_prefix' => '/images/users',
            'upload_destination' => __DIR__,
        ]);

        return $mapping;
    }

    private function createUploadedFile(string $name = 'user_example.jpg'): UploadedFile
    {
        $path = $this->getImagesPath() . $name;

        return new UploadedFile($path, $name);
    }

    private function createUser(?UploadedFile $file = null): User
    {
        $file ??= new UploadedFile('fake', 'fake', error: -1);

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

    private function getNamer(?UserNamer $namer = null): UserNamer
    {
        return $namer ?? new UserNamer();
    }
}
