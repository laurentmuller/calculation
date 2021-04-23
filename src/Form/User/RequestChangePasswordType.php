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

use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Type to request change password of a user.
 *
 * @author Laurent Muller
 */
class RequestChangePasswordType extends AbstractUserCaptchaType
{
    private string $remote;

    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application, UrlGeneratorInterface $generator)
    {
        $this->remote = $generator->generate('ajax_check_exist');
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('resetting.request.username')
            ->className('user-name')
            ->updateAttribute('remote', $this->remote)
            ->add(UserNameType::class);

        parent::addFormFields($helper);
    }
}
