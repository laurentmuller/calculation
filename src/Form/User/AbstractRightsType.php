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
use App\Form\DataTransformer\RightsTransformer;
use App\Form\FormHelper;
use App\Interfaces\RoleInterface;
use App\Service\RoleService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract class to edit permissions rights.
 *
 * @template TModel of RoleInterface
 *
 * @extends AbstractHelperType<TModel>
 */
abstract class AbstractRightsType extends AbstractHelperType
{
    public function __construct(
        private readonly RoleService $service,
        private readonly RightsTransformer $transformer
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', $this->getDataClass());
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('rights')
            ->modelTransformer($this->transformer)
            ->add(RightsType::class);
    }

    protected function addRoleType(FormHelper $helper, string $field): void
    {
        $helper->field($field)
            ->label('user.fields.role')
            ->updateOptions([
                'prepend_icon' => 'fa-solid fa-user-tag',
                'value_transformer' => $this->service->translateRole(...),
            ])
            ->addPlainType();
    }

    /**
     * @return class-string<TModel>
     */
    abstract protected function getDataClass(): string;

    #[\Override]
    protected function getLabelPrefix(): string
    {
        return 'user.fields.';
    }

    protected function translateEnabled(string $value): string
    {
        $enabled = \filter_var($value, \FILTER_VALIDATE_BOOLEAN);

        return $this->service->translateEnabled($enabled);
    }
}
