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

use App\Enums\TableView;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Service\UrlGeneratorService;
use App\Table\DataQuery;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Value resolver for {@link DataQuery}.
 */
final readonly class DataQueryValueResolver implements SortModeInterface, ValueResolverInterface
{
    use CookieTrait;

    public function __construct(
        private PropertyAccessorInterface $accessor,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (DataQuery::class !== $argument->getType()) {
            return [];
        }

        $query = $this->createQuery($argument);
        $this->updateQuery($query, $request->query);
        $this->updateParameters($query, $request);
        $this->validateQuery($query);

        return [$query];
    }

    private function createQuery(ArgumentMetadata $argument): DataQuery
    {
        if ($argument->hasDefaultValue()) {
            /** @psalm-var DataQuery */
            return $argument->getDefaultValue();
        }

        return new DataQuery();
    }

    private function formatError(string $key, string|\Stringable $message): string
    {
        return \sprintf('%s.%s: %s', DataQuery::class, $key, $message);
    }

    private function getLimit(Request $request, string $prefix, TableView $view): int
    {
        return $this->getCookieInt($request, TableInterface::PARAM_LIMIT, $prefix, $view->getPageSize());
    }

    /**
     * @psalm-return self::SORT_*
     */
    private function getOrder(Request $request, string $prefix): string
    {
        /** @psalm-var self::SORT_* */
        return $this->getCookieString($request, TableInterface::PARAM_ORDER, $prefix, self::SORT_ASC);
    }

    private function getPrefix(Request $request): string
    {
        return \strtoupper($request->attributes->getString('_route'));
    }

    private function getSort(Request $request, string $prefix): string
    {
        return $this->getCookieString($request, TableInterface::PARAM_SORT, $prefix);
    }

    private function getView(Request $request, TableView $default): TableView
    {
        return $this->getCookieEnum($request, TableInterface::PARAM_VIEW, $default);
    }

    private function isCallback(Request $request): bool
    {
        return $request->isXmlHttpRequest();
    }

    private function mapErrors(ConstraintViolationListInterface $errors): string
    {
        $str = '';
        foreach ($errors as $error) {
            $str .= $this->formatError($error->getPropertyPath(), $error->getMessage()) . "\n";
        }

        return \rtrim($str);
    }

    private function updateParameters(DataQuery $query, Request $request): void
    {
        $query->prefix = $this->getPrefix($request);
        $query->callback = $this->isCallback($request);
        $query->view = $this->getView($request, $query->view);
        if (0 === $query->limit) {
            $query->limit = $this->getLimit($request, $query->prefix, $query->view);
        }
        if ('' === $query->sort) {
            $query->sort = $this->getSort($request, $query->prefix);
            $query->order = $this->getOrder($request, $query->prefix);
        }
    }

    /**
     * @psalm-param InputBag<string> $inputBag
     */
    private function updateQuery(DataQuery $query, InputBag $inputBag): void
    {
        /** @psalm-var mixed $value */
        foreach ($inputBag as $key => $value) { // @phpstan-ignore varTag.type
            if (UrlGeneratorService::PARAM_CALLER === $key) {
                continue;
            }
            if (!$this->accessor->isWritable($query, $key)) {
                $message = $this->formatError($key, $this->translator->trans('schema.fields.error'));
                throw new BadRequestHttpException($message);
            }
            if (TableInterface::PARAM_VIEW === $key) {
                $value = TableView::tryFrom((string) $value) ?? $query->view; // @phpstan-ignore property.nonObject
            }
            $this->accessor->setValue($query, $key, $value);
        }
    }

    /**
     * @throws BadRequestException
     */
    private function validateQuery(DataQuery $query): void
    {
        $errors = $this->validator->validate($query);
        if (\count($errors) > 0) {
            $message = $this->mapErrors($errors);
            $previous = new ValidationFailedException($query, $errors);
            throw new BadRequestHttpException($message, $previous);
        }
    }
}
