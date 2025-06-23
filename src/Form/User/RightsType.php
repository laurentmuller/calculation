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
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use Elao\Enum\FlagBag;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The access rights type.
 *
 * @extends AbstractType<mixed>
 */
class RightsType extends AbstractType
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        private readonly Security $security,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entities = EntityName::sorted();
        foreach ($entities as $entity) {
            if (!$this->isGranted($entity)) {
                continue;
            }
            $this->addEntityPermissionType($builder, $entity);
        }
    }

    /**
     * @phpstan-param FormBuilderInterface<mixed> $builder
     */
    private function addEntityPermissionType(FormBuilderInterface $builder, EntityName $entity): void
    {
        $builder->add(
            $entity->getFormField(),
            EntityPermissionType::class,
            [
                'label' => $entity,
                /**
                 * @phpstan-param int[] $object
                 */
                'getter' => fn (array $object): FlagBag => $this->getValue($entity, $object),
                /**
                 * @phpstan-param int[] $object
                 * @phpstan-param FlagBag<EntityPermission> $value
                 */
                'setter' => fn (array &$object, FlagBag $value) => $this->setValue($entity, $object, $value),
            ]
        );
    }

    /**
     * @phpstan-param int[] $object
     *
     * @phpstan-return FlagBag<EntityPermission>
     */
    private function getValue(EntityName $entity, array $object): FlagBag
    {
        $offset = $entity->offset();
        $value = $object[$offset];

        return new FlagBag(EntityPermission::class, $value);
    }

    private function isGranted(EntityName $entityName): bool
    {
        return match ($entityName) {
            EntityName::CALCULATION,
            EntityName::CALCULATION_STATE,
            EntityName::CATEGORY,
            EntityName::GLOBAL_MARGIN,
            EntityName::GROUP,
            EntityName::PRODUCT,
            EntityName::TASK => true,
            EntityName::CUSTOMER => $this->debug,
            EntityName::LOG,
            EntityName::USER => $this->security->isGranted(RoleInterface::ROLE_ADMIN),
        };
    }

    /**
     * @phpstan-param int[] $object
     * @phpstan-param FlagBag<EntityPermission> $value
     */
    private function setValue(EntityName $entity, array &$object, FlagBag $value): void
    {
        $offset = $entity->offset();
        $object[$offset] = $value->getValue();
    }
}
