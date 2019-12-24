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

use App\Entity\User;
use App\Form\Type\RightsType;
use App\Utils\Utils;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User rights type.
 *
 * @author Laurent Muller
 */
class UserRightsType extends RightsType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, RoleHierarchyInterface $roleHierarchy, TranslatorInterface $translator)
    {
        parent::__construct($kernel, $roleHierarchy);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    /**
     * Translate the given role.
     *
     * @param string $role the role name
     *
     * @return string the translated role
     */
    public function translateRole(string $role): string
    {
        return Utils::translateRole($this->translator, $role);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFields(FormHelper $helper): void
    {
        parent::addFields($helper);

        $helper->field('username')
            ->label('user.fields.username')
            ->addPlainType(true);

        $helper->field('role')
            ->label('user.fields.role')
            ->updateOption('transformer', [$this, 'translateRole'])
            ->addPlainType(true);

        $helper->field('overwrite')
            ->label('user.fields.overwrite')
            ->notRequired()
            ->addCheckboxType();
    }
}
