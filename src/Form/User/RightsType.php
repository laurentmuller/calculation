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

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\RoleInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * The access rights type.
 *
 * @author Laurent Muller
 */
class RightsType extends AbstractHelperType
{
    /**
     * Debug mode.
     */
    protected bool $debug;

    /**
     * Role hierarchy.
     */
    protected RoleHierarchyInterface $roleHierarchy;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, RoleHierarchyInterface $roleHierarchy)
    {
        $this->debug = $kernel->isDebug();
        $this->roleHierarchy = $roleHierarchy;
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
            $event->getForm()->remove(EntityVoterInterface::ENTITY_USER);
        }
    }

    /**
     * Add fields to the given helper.
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // add listener
        $helper->addPreSetDataListener([$this, 'onPreSetData']);

        $this->addRightType($helper, EntityVoterInterface::ENTITY_CALCULATION, 'calculation.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_PRODUCT, 'product.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_TASK, 'task.list.title')
            ->addRightType($helper, EntityVoterInterface::ENTITY_GROUP, 'group.list.title')
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

        return $this;
    }
}
