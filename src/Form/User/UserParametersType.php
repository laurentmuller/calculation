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

use App\Form\FormHelper;
use App\Form\Parameters\AbstractParametersType;
use App\Service\ApplicationService;
use Symfony\Component\Security\Core\Security;

/**
 * Type for user's  parameters.
 */
class UserParametersType extends AbstractParametersType
{
    /**
     * Constructor.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(Security $security, ApplicationService $service)
    {
        parent::__construct($security, $service->getProperties());
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'parameters';
    }

    /**
     * {@inheritdoc}
     */
    protected function addSections(FormHelper $helper): void
    {
        if ($this->superAdmin) {
            $this->addHomePageSection($helper);
        }
        $this->addMessageSection($helper);
        $this->addDisplaySection($helper);
        if ($this->superAdmin) {
            $this->addOptionsSection($helper);
        }
    }
}
