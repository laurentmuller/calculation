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

namespace App\Form\User;

use App\Enums\Importance;
use App\Form\DataTransformer\AddressTransformer;
use App\Form\FormHelper;
use App\Form\Type\SimpleEditorType;
use App\Model\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Abstract comment type.
 *
 * @extends AbstractType<Comment>
 */
class UserCommentType extends AbstractType
{
    /**
     * @phpstan-param FormBuilderInterface<Comment|null> $builder
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = new FormHelper($builder, 'user.fields.');
        $helper->field('to')
            ->updateOption('prepend_icon', 'fa-solid fa-user')
            ->modelTransformer(new AddressTransformer())
            ->addPlainType();

        $helper->field('subject')
            ->updateOption('prepend_icon', 'fa-regular fa-message')
            ->addTextType();

        $helper->field('message')
            ->minLength(10)
            ->add(SimpleEditorType::class);

        $helper->field('importance')
            ->label('importance.name')
            ->updateOption('prepend_icon', 'fa-solid fa-exclamation')
            ->addEnumType(Importance::class);

        $helper->field('attachments')
            ->updateOptions([
                'multiple' => true,
                'maxfiles' => 3,
                'maxsize' => '10mi',
                'maxsizetotal' => '30mi', ])
            ->notRequired()
            ->addFileType();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }
}
