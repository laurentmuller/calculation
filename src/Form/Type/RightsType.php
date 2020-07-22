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

namespace App\Form\Type;

use App\Form\FormHelper;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\RoleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * The access rights type.
 *
 * @author Laurent Muller
 */
class RightsType extends AbstractType
{
    /**
     * Debug mode.
     *
     * @var bool
     */
    protected $debug;

    /**
     * @var RoleHierarchyInterface
     */
    protected $roleHierarchy;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, RoleHierarchyInterface $roleHierarchy)
    {
        $this->debug = $kernel->isDebug();
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // add fields
        $helper = new FormHelper($builder);
        $this->addFields($helper);

        // add listener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * Handles the preset data event.
     */
    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        if ($data instanceof RoleInterface) {
            $roles = $this->roleHierarchy->getReachableRoleNames([$data->getRole()]);
        } else {
            $roles = [];
        }

        if (!\in_array(RoleInterface::ROLE_ADMIN, $roles, true)) {
            $form = $event->getForm();
            $form->remove(EntityVoterInterface::ENTITY_USER);
        }
    }

    /**
     * Add fields to the given helper.
     */
    protected function addFields(FormHelper $helper): void
    {
        $this->addRightType($helper, EntityVoterInterface::ENTITY_CALCULATION, 'calculation.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_PRODUCT, 'product.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_CATEGORY, 'category.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_CALCULATION_STATE, 'calculationstate.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_GLOBAL_MARGIN, 'globalmargin.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_USER, 'user.list.title');

        if ($this->debug) {
            $this->addRightType($helper, EntityVoterInterface::ENTITY_CUSTOMER, 'customer.list.title');
        }
    }

    /**
     * Adds an attribute rights type.
     *
     * @param FormHelper $helper the form helper
     * @param string     $field  the field name
     * @param string     $label  the field label
     */
    protected function addRightType(FormHelper $helper, string $field, string $label): self
    {
        $helper->field($field)
            ->label($label)
            ->add(AttributeRightType::class);

        return  $this;
    }
}
