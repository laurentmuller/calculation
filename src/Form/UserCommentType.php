<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type to send a comment.
 *
 * @author Laurent Muller
 */
class UserCommentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $isMail = $options['data']->isMail();
        $helper = new FormHelper($builder, 'user.fields.');

        if ($isMail) {
            $helper->field('to')
                ->addPlainType(true);

            $helper->field('subject')
                ->addTextType();
        } else {
            $helper->field('from')
                ->addPlainType(true);

            $helper->field('subject')
                ->addPlainType(true);
        }

        $helper->field('message')
            ->minLength(10)
            ->addEditorType();

        $helper->field('attachments')
            ->updateOption('multiple', true)
            ->updateOption('maxfiles', 3)
            ->updateOption('maxsize', '10mi')
            ->notRequired()
            ->addFileType();
    }
}
