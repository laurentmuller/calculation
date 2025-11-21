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

use App\Enums\Importance;
use App\Form\Extension\FileTypeExtension;
use App\Form\Extension\InputGroupTypeExtension;
use App\Form\Type\PlainType;
use App\Form\Type\SimpleEditorType;
use App\Form\User\UserCommentType;
use App\Model\Comment;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;

final class UserCommentTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testSubmitValidData(): void
    {
        $model = new Comment();
        $data = [
            'fromAddress' => 'fromAddress@example.com',
            'toAddress' => 'toAddress@example.com',
            'subject' => 'subject',
            'message' => 'message',
            'importance' => Importance::HIGH,
            'attachments' => null,
        ];
        $form = $this->factory->create(UserCommentType::class, $model);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->createMockTranslator()),
            new UserCommentType(),
            new SimpleEditorType(''),
        ];
    }

    /**
     * @phpstan-return FormTypeExtensionInterface<mixed>[]
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
            new InputGroupTypeExtension(),
        ];
    }
}
