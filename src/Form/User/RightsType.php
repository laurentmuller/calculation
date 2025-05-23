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
use App\Service\RoleHierarchyService;
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
        private readonly RoleHierarchyService $service
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
            $this->addRightType($helper, $entity);
        }
        $helper->listenerPreSetData($this->onPreSetData(...));
    }

    private function addRightType(FormHelper $helper, EntityName $entity): void
    {
        $helper->field($entity->getRightsField())
            ->label($entity)
            ->add(AttributeRightType::class);
    }

    private function onPreSetData(PreSetDataEvent $event): void
    {
        /** @phpstan-var mixed $data */
        $data = $event->getData();
        $form = $event->getForm();
        if (!$this->service->hasRole($data, RoleInterface::ROLE_ADMIN)) {
            $form->remove(EntityName::LOG->getRightsField());
            $form->remove(EntityName::USER->getRightsField());
        }
        if (!$this->debug) {
            $form->remove(EntityName::CUSTOMER->getRightsField());
        }
    }
}
