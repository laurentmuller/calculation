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

use App\Enums\EntityPermission;
use App\Service\EntityNameService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The access permissions type.
 *
 * @extends AbstractType<EntityPermission[]>
 */
class RightsType extends AbstractType
{
    public function __construct(private readonly EntityNameService $service)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entities = $this->service->getEntities();
        foreach ($entities as $entity) {
            $builder->add(
                $entity->getFormField(),
                EntityPermissionType::class,
                ['label' => $entity]
            );
        }
    }
}
