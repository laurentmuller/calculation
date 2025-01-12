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

namespace App\Resolver;

use App\Model\CalculationGroupQuery;
use App\Model\CalculationQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @psalm-type GroupType = array{id: string, total: string}
 * @psalm-type SourceType = array{adjust: string, userMargin: string, groups: GroupType[]}
 */
class CalculationQueryResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (CalculationQuery::class !== $argument->getType()) {
            return [];
        }

        $source = $request->getPayload()->all();
        if ([] === $source) {
            return [$this->createQuery($argument)];
        }

        /** @psalm-var SourceType $source */
        $query = new CalculationQuery(
            $this->isAdjust($source),
            $this->getUserMargin($source),
            $this->getGroups($source)
        );

        return [$query];
    }

    /**
     * @psalm-param GroupType $group
     */
    private function createGroup(array $group): CalculationGroupQuery
    {
        return new CalculationGroupQuery(
            (int) $group['id'],
            (float) $group['total']
        );
    }

    private function createQuery(ArgumentMetadata $argument): CalculationQuery
    {
        if ($argument->hasDefaultValue()) {
            /** @psalm-var CalculationQuery */
            return $argument->getDefaultValue();
        }

        return new CalculationQuery();
    }

    /**
     * @psalm-param SourceType $source
     *
     * @return CalculationGroupQuery[]
     */
    private function getGroups(array $source): array
    {
        return \array_map(
            fn (array $group): CalculationGroupQuery => $this->createGroup($group),
            $source['groups']
        );
    }

    /**
     * @psalm-param SourceType $source
     */
    private function getUserMargin(array $source): float
    {
        return (float) $source['userMargin'];
    }

    /**
     * @psalm-param SourceType $source
     */
    private function isAdjust(array $source): bool
    {
        return \filter_var($source['adjust'], \FILTER_VALIDATE_BOOLEAN);
    }
}
