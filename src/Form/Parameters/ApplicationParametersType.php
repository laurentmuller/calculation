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

namespace App\Form\Parameters;

use App\Interfaces\RoleInterface;
use App\Parameter\ApplicationParameters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractHelperParametersType<ApplicationParameters>
 */
class ApplicationParametersType extends AbstractHelperParametersType
{
    public function __construct(private readonly Security $security)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->add('customer', CustomerParameterType::class);
        $builder->add('default', DefaultParameterType::class);
        $builder->add('product', ProductParameterType::class);
        if ($this->isSuperAdmin()) {
            $builder->add('security', SecurityParameterType::class);
        }
    }

    #[\Override]
    protected function getParametersClass(): string
    {
        return ApplicationParameters::class;
    }

    private function isSuperAdmin(): bool
    {
        return $this->security->isGranted(RoleInterface::ROLE_SUPER_ADMIN);
    }
}
