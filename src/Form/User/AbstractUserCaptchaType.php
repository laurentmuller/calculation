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

use App\Constraint\Captcha;
use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Form\Type\CaptchaImageType;
use App\Parameter\ApplicationParameters;
use App\Security\SecurityAttributes;
use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Abstract form type for user entities with a captcha field (if applicable).
 *
 * @extends AbstractHelperType<mixed>
 */
abstract class AbstractUserCaptchaType extends AbstractHelperType
{
    public function __construct(
        protected readonly CaptchaImageService $service,
        protected readonly ApplicationParameters $parameters
    ) {
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Add form fields.
     *
     * Subclass must call <code>parent::addFormFields($helper);</code> to add
     * the captcha field (if applicable).
     *
     * @throws \Exception
     */
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        if (!$this->parameters->getSecurity()->isCaptcha()) {
            return;
        }
        $helper->field(SecurityAttributes::CAPTCHA_FIELD)
            ->label('captcha.label')
            ->constraints(new NotBlank(), new Captcha())
            ->updateOption('image', $this->service->generateImage())
            ->add(CaptchaImageType::class);
    }
}
