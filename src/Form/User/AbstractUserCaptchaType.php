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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Form\Type\CaptchaImageType;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Validator\Captcha;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Abstract form type for user with a captcha field (if applicable).
 *
 * @author Laurent Muller
 */
abstract class AbstractUserCaptchaType extends AbstractHelperType
{
    /**
     * The display captcha image flag.
     */
    protected bool $displayCaptcha;

    /**
     * The service.
     */
    protected CaptchaImageService $service;

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
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Add form fields.
     *
     * Subclass must call <code>parent::addFormFields($helper);</code> to add
     * the image captcha field (if applicable).
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // captcha image
        if ($this->displayCaptcha) {
            $helper->field('_captcha')
                ->label('captcha.label')
                ->updateOption('image', $this->service->generateImage(false))
                ->updateOption('constraints', [
                    new NotBlank(),
                    new Captcha(),
                ])
                ->add(CaptchaImageType::class);
        }
    }
}
