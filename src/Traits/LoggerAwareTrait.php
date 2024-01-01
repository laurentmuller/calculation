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

namespace App\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

/**
 * Extends logger trait wih the subscribed service.
 *
 * @property \Psr\Container\ContainerInterface $container
 *
 * @psalm-require-implements \Symfony\Contracts\Service\ServiceSubscriberInterface
 */
trait LoggerAwareTrait
{
    use LoggerTrait;

    private ?LoggerInterface $logger = null;

    #[SubscribedService]
    public function getLogger(): LoggerInterface
    {
        if ($this->logger instanceof LoggerInterface) {
            return $this->logger;
        }
        $id = self::class . '::' . __FUNCTION__;
        if (!$this->container->has($id)) {
            throw new \LogicException(\sprintf('Unable to find service "%s".', $id));
        }

        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->logger = $this->container->get($id);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }
}
