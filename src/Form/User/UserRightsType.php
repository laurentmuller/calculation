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

namespace App\Form\User;

use App\Entity\User;
use App\Form\FormHelper;
use App\Util\Utils;
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

        $resolver->setDefaults(['data_class' => User::class]);
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
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);

        $helper->field('username')
            ->addPlainType(true);

        $helper->field('role')
            ->updateOption('transformer', [$this, 'translateRole'])
            ->addPlainType(true);

        $helper->field('overwrite')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
