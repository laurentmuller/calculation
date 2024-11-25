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

namespace App\Tests\Form\Type;

use App\Form\Type\RepeatPasswordType;
use App\Tests\Form\User\PasswordHasherExtensionTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;

class RepeatPasswordTypeTest extends TypeTestCase
{
    use PasswordHasherExtensionTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(RepeatPasswordType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
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
