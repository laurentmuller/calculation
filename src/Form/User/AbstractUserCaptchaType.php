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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Form\Type\CaptchaImageType;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Validator\Captcha;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Abstract form type for user with a captcha field (if applicable).
 */
abstract class AbstractUserCaptchaType extends AbstractHelperType
{
    /**
     * The display captcha image flag.
     */
    protected bool $displayCaptcha;

    /**
     * Constructor.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(protected CaptchaImageService $service, ApplicationService $application)
    {
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
     *
     * @throws \Exception
     */
    protected function addFormFields(FormHelper $helper): void
    {
        if ($this->displayCaptcha) {
            $helper->field('captcha')
                ->label('captcha.label')
                ->constraints(new NotBlank(), new Captcha())
                ->updateOption('image', $this->service->generateImage())
                ->add(CaptchaImageType::class);
        }
    }
}
