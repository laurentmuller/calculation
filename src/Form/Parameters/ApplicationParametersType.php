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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Interfaces\RoleInterface;
use App\Parameter\ApplicationParameters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationParametersType extends AbstractHelperType
{
    public const DEFAULT_VALUES = 'default_values';

    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplicationParameters::class,
        ]);

        $resolver->setDefault(self::DEFAULT_VALUES, [])
            ->setAllowedTypes(self::DEFAULT_VALUES, 'array');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $this->addParameterType($helper, 'customer', CustomerParameterType::class);
        $this->addParameterType($helper, 'default', DefaultParameterType::class);
        $this->addParameterType($helper, 'display', DisplayParameterType::class);
        $this->addParameterType($helper, 'homePage', HomePageParameterType::class);
        $this->addParameterType($helper, 'message', MessageParameterType::class);
        $this->addParameterType($helper, 'options', OptionsParameterType::class);
        $this->addParameterType($helper, 'product', ProductParameterType::class);
        if ($this->isSuperAdmin()) {
            $this->addParameterType($helper, 'security', SecurityParameterType::class);
        }
    }

    /**
     * @psalm-template T of AbstractParameterType
     *
     * @psalm-param class-string<T> $class
     */
    private function addParameterType(FormHelper $helper, string $name, string $class): void
    {
        $helper->field($name)
            ->label(false)
            ->add($class);
    }

    private function isSuperAdmin(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof RoleInterface && $user->isSuperAdmin();
    }
}
