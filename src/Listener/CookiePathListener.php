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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class CookiePathListener
{
    public function __construct(
        #[Autowire(param: 'cookie_path')]
        private string $cookiePath,
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -1000)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $basePath = $event->getRequest()->getBasePath();
        if ('' === $basePath || '/' === $basePath) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;
        $cookies = $headers->getCookies();
        if ([] === $cookies) {
            return;
        }

        foreach ($cookies as $cookie) {
            if ('/' !== $cookie->getPath()) {
                continue;
            }
            $headers->removeCookie(
                $cookie->getName(),
                $cookie->getPath(),
                $cookie->getDomain()
            );
            $headers->setCookie(
                $cookie->withPath($this->cookiePath)
            );
        }
    }
}
