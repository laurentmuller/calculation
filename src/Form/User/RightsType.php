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

use App\Enums\EntityName;
use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Interfaces\RoleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * The access rights type.
 */
class RightsType extends AbstractHelperType
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
        #[Autowire('%kernel.debug%')]
        private readonly bool $isDebug
    ) {
    }

    /**
     * Handles the preset data event.
     */
    public function onPreSetData(FormEvent $event): void
    {
        if (!$this->hasRole($event->getData(), RoleInterface::ROLE_SUPER_ADMIN)) {
            $event->getForm()->remove(EntityName::LOG->value);
        }
        if (!$this->hasRole($event->getData(), RoleInterface::ROLE_ADMIN)) {
            $event->getForm()->remove(EntityName::USER->value);
        }
        if (!$this->isDebug) {
            $event->getForm()->remove(EntityName::CUSTOMER->value);
        }
    }

    /**
     * Add fields to the given helper.
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // add listener
        $helper->addPreSetDataListener(function (FormEvent $event): void {
            $this->onPreSetData($event);
        });

        $entities = EntityName::sorted();
        foreach ($entities as $entity) {
            $this->addRightType($helper, $entity);
        }
    }

    /**
     * Adds an attribute rights type.
     */
    private function addRightType(FormHelper $helper, EntityName $entity): void
    {
        $helper->field($entity->value)
            ->label($entity->getReadable())
            ->add(AttributeRightType::class);
    }

    private function hasRole(mixed $data, string $role): bool
    {
        if ($data instanceof RoleInterface) {
            $roles = $this->roleHierarchy->getReachableRoleNames([$data->getRole()]);

            return \in_array($role, $roles, true);
        }

        return false;
    }
}
