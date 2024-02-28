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

namespace App\Listener;

use App\Enums\TableView;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Table\DataQuery;
use App\Traits\CookieTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handle the kernel controller arguments event to update properties of data query.
 */
class DataQueryKernelListener implements SortModeInterface
{
    use CookieTrait;

    /**
     * @psalm-api
     */
    #[AsEventListener(event: KernelEvents::CONTROLLER_ARGUMENTS, priority: -10)]
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $arguments = $event->getArguments();
        if ([] === $arguments) {
            return;
        }

        /** @psalm-var ?DataQuery $argument */
        foreach ($arguments as $index => $argument) {
            if ($argument instanceof DataQuery) {
                $arguments[$index] = $this->updateQuery($argument, $event->getRequest());
                $event->setArguments($arguments);
                break;
            }
        }
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
        /** @psalm-var self::SORT_* $order */
        $order = $this->getCookieString($request, TableInterface::PARAM_ORDER, $prefix, self::SORT_ASC);

        return $order;
    }

    private function getPrefix(Request $request): string
    {
        return \strtoupper($request->attributes->getString('_route'));
    }

    private function getSort(Request $request, string $prefix): string
    {
        return $this->getCookieString($request, TableInterface::PARAM_SORT, $prefix);
    }

    private function getView(Request $request): TableView
    {
        return $this->getCookieEnum($request, TableInterface::PARAM_VIEW, TableView::TABLE);
    }

    private function isCallback(Request $request): bool
    {
        return $request->isXmlHttpRequest();
    }

    private function updateQuery(DataQuery $query, Request $request): DataQuery
    {
        $query->view = $this->getView($request);
        $query->prefix = $this->getPrefix($request);
        $query->callback = $this->isCallback($request);
        if (0 === $query->limit) {
            $query->limit = $this->getLimit($request, $query->prefix, $query->view);
        }
        if ('' === $query->sort) {
            $query->sort = $this->getSort($request, $query->prefix);
            $query->order = $this->getOrder($request, $query->prefix);
        }

        return $query;
    }
}
