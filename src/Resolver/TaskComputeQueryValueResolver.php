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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Value resolver for {@link TaskComputeQuery}.
 */
final readonly class TaskComputeQueryValueResolver implements ValueResolverInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (TaskComputeQuery::class !== $argument->getType()) {
            return [];
        }

        $query = $this->createQuery($request);
        $errors = $this->validator->validate($query);
        if (\count($errors) > 0) {
            $message = $this->mapErrors($errors);
            $previous = new ValidationFailedException($query, $errors);
            throw new BadRequestHttpException($message, $previous);
        }

        return [$query];
    }

    private function createQuery(Request $request): TaskComputeQuery
    {
        $payload = $request->getPayload();
        $id = $payload->getInt('id');
        $quantity = (float) $payload->get('quantity', 1.0);
        $items = \array_map('intval', $payload->all('items'));

        return new TaskComputeQuery($id, $quantity, $items);
    }

    private function mapErrors(ConstraintViolationListInterface $errors): string
    {
        $str = '';
        $class = TaskComputeQuery::class;
        foreach ($errors as $error) {
            $str .= \sprintf("%s.%s: %s\n", $class, $error->getPropertyPath(), $error->getMessage());
        }

        return \rtrim($str);
    }
}
