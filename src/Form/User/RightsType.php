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
use App\Service\RoleService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Event\PreSetDataEvent;

/**
 * The access rights type.
 */
class RightsType extends AbstractHelperType
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        protected readonly RoleService $service
    ) {
    }

    /**
     * Add fields to the given helper.
     */
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $entities = EntityName::sorted();
        foreach ($entities as $entity) {
            $this->addEntityPermissionType($helper, $entity);
        }
        $helper->listenerPreSetData($this->onPreSetData(...));
    }

    protected function translateEnabled(string $enabled): string
    {
        $enabled = \filter_var($enabled, \FILTER_VALIDATE_BOOLEAN);

        return $this->service->translateEnabled($enabled);
    }

    protected function translateRole(RoleInterface|string $role): string
    {
        return $this->service->translateRole($role);
    }

    private function addEntityPermissionType(FormHelper $helper, EntityName $entity): void
    {
        $helper->field($entity->getFormField())
            ->label($entity)
            ->add(EntityPermissionType::class);
    }

    private function onPreSetData(PreSetDataEvent $event): void
    {
        /** @phpstan-var ?RoleInterface $data */
        $data = $event->getData();
        $form = $event->getForm();
        if (!$this->service->hasRole($data, RoleInterface::ROLE_ADMIN)) {
            $form->remove(EntityName::LOG->getFormField());
            $form->remove(EntityName::USER->getFormField());
        }
        if (!$this->debug) {
            $form->remove(EntityName::CUSTOMER->getFormField());
        }
    }
}
