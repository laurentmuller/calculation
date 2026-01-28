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

use App\Model\TaskComputeQuery;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Value resolver for {@link TaskComputeQuery}.
 */
final readonly class TaskComputeQueryValueResolver extends AbstractValueResolver
{
    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if (TaskComputeQuery::class !== $argument->getType()) {
            return [];
        }

        $query = $this->createQuery($argument);
        $this->updateQuery($query, $request->request);
        $this->validate($query);

        return [$query];
    }

    private function createQuery(ArgumentMetadata $argument): TaskComputeQuery
    {
        return $argument->hasDefaultValue() ? $argument->getDefaultValue() : new TaskComputeQuery();
    }

    /**
     * @phpstan-param InputBag<bool|float|int|string> $inputBag
     */
    private function updateQuery(TaskComputeQuery $query, InputBag $inputBag): void
    {
        $query->id = $inputBag->getInt('id');
        $query->quantity = (float) $inputBag->get('quantity', 1.0);
        $query->items = \array_map(intval(...), $inputBag->all('items'));
    }
}
