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
use App\Table\AbstractCategoryItemTable;
use App\Table\CalculationTable;
use App\Table\CategoryTable;
use App\Table\DataQuery;
use App\Table\LogTable;
use App\Table\SearchTable;
use App\Traits\CookieTrait;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Value resolver for {@link DataQuery}.
 */
final readonly class DataQueryValueResolver implements SortModeInterface, ValueResolverInterface
{
    use CookieTrait;

    public function __construct(
        #[Autowire('%cookie_path%')]
        private string $cookiePath,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @throws BadRequestException
     */
    #[\Override]
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

    #[\Override]
    protected function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    private function createQuery(ArgumentMetadata $argument): DataQuery
    {
        if ($argument->hasDefaultValue()) {
            /** @phpstan-var DataQuery */
            return $argument->getDefaultValue();
        }

        return new DataQuery();
    }

    private function formatError(string $key, string|\Stringable $message): string
    {
        return \sprintf('%s.%s: %s', DataQuery::class, $key, $message);
    }

    private function getLimit(Request $request, TableView $view, string $prefix): int
    {
        return $this->getCookieInt($request, TableInterface::PARAM_LIMIT, $view->getPageSize(), $prefix);
    }

    /**
     * @phpstan-return self::SORT_*
     */
    private function getOrder(Request $request, string $prefix): string
    {
        /** @phpstan-var self::SORT_* */
        return $this->getCookieString($request, TableInterface::PARAM_ORDER, self::SORT_ASC, $prefix);
    }

    private function getPrefix(Request $request): string
    {
        return \strtoupper($request->attributes->getString('_route'));
    }

    private function getSort(Request $request, string $prefix): string
    {
        return $this->getCookieString($request, TableInterface::PARAM_SORT, '', $prefix);
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
            $query->limit = $this->getLimit($request, $query->view, $query->prefix);
        }
        if ('' === $query->sort) {
            $query->sort = $this->getSort($request, $query->prefix);
            $query->order = $this->getOrder($request, $query->prefix);
        }
    }

    /**
     * @phpstan-param InputBag<string> $inputBag
     */
    private function updateQuery(DataQuery $query, InputBag $inputBag): void
    {
        /** @var string[] $keys */
        $keys = $inputBag->keys();
        foreach ($keys as $key) {
            match ($key) {
                UrlGeneratorService::PARAM_CALLER => true,
                TableInterface::PARAM_ID => $query->id = $inputBag->getInt($key),
                TableInterface::PARAM_SEARCH => $query->search = $inputBag->getString($key),
                TableInterface::PARAM_SORT => $query->sort = $inputBag->getString($key),
                TableInterface::PARAM_ORDER => $query->order = $this->validateOrder($inputBag->getString($key)),
                TableInterface::PARAM_OFFSET => $query->offset = $inputBag->getInt($key),
                TableInterface::PARAM_LIMIT => $query->limit = $inputBag->getInt($key),
                TableInterface::PARAM_VIEW => $query->view = $inputBag->getEnum($key, TableView::class, $query->view),
                CategoryTable::PARAM_GROUP,
                CalculationTable::PARAM_STATE,
                CalculationTable::PARAM_EDITABLE,
                AbstractCategoryItemTable::PARAM_CATEGORY => $query->addParameter($key, $inputBag->getInt($key)),
                LogTable::PARAM_LEVEL,
                LogTable::PARAM_CHANNEL,
                SearchTable::PARAM_ENTITY => $query->addParameter($key, $inputBag->getString($key)),
                default => throw new BadRequestHttpException(\sprintf('Invalid parameter "%s".', $key)),
            };
        }
    }

    /**
     * @phpstan-return self::SORT_*
     */
    private function validateOrder(string $order): string
    {
        return StringUtils::equalIgnoreCase(self::SORT_DESC, $order) ? self::SORT_DESC : self::SORT_ASC;
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
