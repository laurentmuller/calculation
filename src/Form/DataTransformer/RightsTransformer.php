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

namespace App\Form\DataTransformer;

use App\Enums\EntityPermission;
use App\Service\EntityNameService;
use Elao\Enum\FlagBag;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<?int, FlagBag<EntityPermission>[]>
 */
readonly class RightsTransformer implements DataTransformerInterface
{
    public function __construct(private EntityNameService $service)
    {
    }

    /**
     * @param FlagBag<EntityPermission>[] $value
     */
    #[\Override]
    public function reverseTransform(mixed $value): int
    {
        $result = 0;
        $entites = $this->service->getEntities();
        foreach ($entites as $entity) {
            $formField = $entity->getFormField();
            $permission = $value[$formField];
            $result |= $entity->getShiftedValue($permission);
        }

        return $result;
    }

    /**
     * @param ?int $value
     *
     * @return FlagBag<EntityPermission>[]
     */
    #[\Override]
    public function transform(mixed $value): array
    {
        $result = [];
        $entites = $this->service->getEntities();
        foreach ($entites as $entity) {
            $formField = $entity->getFormField();
            $offsetValue = $entity->getOffsetValue($value);
            $result[$formField] = new FlagBag(EntityPermission::class, $offsetValue);
        }

        return $result;
    }
}
