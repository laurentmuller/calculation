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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Form\User\RightsType;
use App\Service\EntityNameService;
use App\Tests\Form\PreloadedExtensionsTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Test\TypeTestCase;

#[AllowMockObjectsWithoutExpectations]
final class RightsTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    private const EntityName DEFAULT = EntityName::CALCULATION;

    public function testFormView(): void
    {
        $children = $this->factory
            ->create(RightsType::class, 0)
            ->createView()
            ->children;
        self::assertCount(1, $children);

        $field = self::DEFAULT->getFormField();
        self::assertArrayHasKey($field, $children);

        $child = $children[$field];
        self::assertCount(\count(EntityPermission::cases()), $child);
    }

    public function testSubmitInvalidValue(): void
    {
        self::expectException(UnexpectedTypeException::class);
        self::expectExceptionMessage('Expected argument of type "numeric", "string" given');
        $this->factory->create(RightsType::class, 'fake');
    }

    public function testSubmitNull(): void
    {
        $form = $this->factory->create(RightsType::class);
        $form->submit([0]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    public function testSubmitValidValue(): void
    {
        $form = $this->factory->create(RightsType::class, 63);
        $form->submit([63]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $service = $this->createMock(EntityNameService::class);
        $service->method('getEntities')
            ->willReturn([self::DEFAULT]);
        $rightsType = new RightsType($service);

        return [$rightsType];
    }
}
