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
use App\Form\User\ResetChangePasswordType;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;

class ResetChangePasswordTypeTest extends TypeTestCase
{
    use IdTrait;
    use PasswordHasherExtensionTrait;
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

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(ResetChangePasswordType::class);
        $form->submit([$this->user->getId()]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }
}
