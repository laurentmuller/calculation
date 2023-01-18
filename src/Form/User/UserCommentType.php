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
 * Type to send a comment.
 */
class UserCommentType extends AbstractType
{
    /**
     * Constructor.
     */
    public function __construct(private readonly AddressTransformer $transformer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder, 'user.fields.');

        /** @var Comment $data */
        $data = $options['data'];
        $address = $data->isMail() ? 'toAddress' : 'fromAddress';
        $helper->field($address)
            ->addPlainType(true);
        $builder->get($address)->addModelTransformer($this->transformer);

        $helper->field('subject')
            ->addTextType();

        $helper->field('message')
            ->minLength(10)
            ->add(SimpleEditorType::class);

        $helper->field('importance')
            ->label('importance.name')
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
}
