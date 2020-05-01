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

use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Validator\Captcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Abstract form type for user. This class add a captcha if applicable.
 *
 * @author Laurent Muller
 */
abstract class FosUserType extends AbstractType
{
    /**
     * @var bool
     */
    protected $displayCaptcha;

    /**
     * @var CaptchaImageService
     */
    protected $service;

    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        $this->service = $service;
        $this->displayCaptcha = $application->isDisplayCaptcha();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // fields
        $helper = new FormHelper($builder);
        $this->addFormFields($helper);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Add form fields.
     *
     * Subclass must call <code>parent::addFormFields($helper);</code> to add
     * the image captcha field (if applicable).
     *
     * @param FormHelper $helper the form helper
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // captcha image
        if ($this->displayCaptcha) {
            $helper->field('_captcha')
                ->updateOption('image', $this->service->generateImage(false))
                ->updateOption('constraints', [
                    new NotBlank(),
                    new Captcha(),
                ])
                ->label('security.login.captcha')
                ->domain('FOSUserBundle')
                ->add(CaptchaImage::class);
        }
    }
}
