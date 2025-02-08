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
use App\Form\User\ResetAllPasswordType;
use App\Repository\UserRepository;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Form\Test\TypeTestCase;

class ResetAllPasswordTypeTest extends TypeTestCase
{
    use IdTrait;
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    private User $user;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('username')
            ->setEmail('email@email.com');
        self::setId($this->user);
        parent::setUp();
    }

    public function testSubmitNoData(): void
    {
        $form = $this->factory->create(ResetAllPasswordType::class);
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(ResetAllPasswordType::class);
        $form->submit([$this->user->getId()]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    protected function getPreloadedExtensions(): array
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('getResettableUsers')
            ->willReturn([$this->user]);
        $translator = $this->createMockTranslator();

        return [
            new ResetAllPasswordType($repository, $translator),
        ];
    }
}
