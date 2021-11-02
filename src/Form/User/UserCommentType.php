<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\User;

use App\Form\DataTransformer\AddressTransformer;
use App\Form\FormHelper;
use App\Form\Type\TinyMceEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type to send a comment.
 *
 * @author Laurent Muller
 */
class UserCommentType extends AbstractType
{
    private AddressTransformer $transformer;

    /**
     * Constructor.
     */
    public function __construct(AddressTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $isMail = $options['data']->isMail();
        $helper = new FormHelper($builder, 'user.fields.');

        if ($isMail) {
            $helper->field('toAddress')
                ->addPlainType(true);
        } else {
            $helper->field('fromAddress')
                ->addPlainType(true);
        }

        $helper->field('subject')
            ->addPlainType(true);

        $helper->field('message')
            ->minLength(10)
            ->add(TinyMceEditorType::class);

        $helper->field('attachments')
            ->updateOptions([
                'multiple' => true,
                'maxfiles' => 3,
                'maxsize' => '10mi',
                'maxsizetotal' => '30mi', ])
            ->notRequired()
            ->addFileType();

        // transformer
        if ($isMail) {
            $builder->get('toAddress')->addModelTransformer($this->transformer);
        } else {
            $builder->get('fromAddress')->addModelTransformer($this->transformer);
        }
    }
}
