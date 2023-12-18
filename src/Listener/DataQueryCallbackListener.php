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

use App\Table\DataQuery;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handle the kernel controller arguments to update the callback property of the data query.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER_ARGUMENTS, method: 'onKernelControllerArguments', priority: -10)]
class DataQueryCallbackListener
{
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $arguments = $event->getArguments();
        if ([] === $arguments) {
            return;
        }

        /** @psalm-var mixed $argument */
        foreach ($arguments as $index => $argument) {
            if ($argument instanceof DataQuery) {
                $argument->callback = $event->getRequest()->isXmlHttpRequest();
                $arguments[$index] = $argument;
                $event->setArguments($arguments);
                break;
            }
        }
    }
}
