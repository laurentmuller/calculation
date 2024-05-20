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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Value resolver for TaskComputeQuery.
 */
final class TaskComputeQueryValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (TaskComputeQuery::class !== $argumentType) {
            return [];
        }

        $payload = $request->getPayload();
        $id = $payload->getInt('id');
        $quantity = (float) $payload->get('quantity', 1.0);
        $items = \array_map('intval', $payload->all('items'));
        $query = new TaskComputeQuery($id, $quantity, $items);

        return [$query];
    }
}
