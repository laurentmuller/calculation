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
 * Extends logger trait within the subscribed service.
 *
 * @psalm-require-implements \Symfony\Contracts\Service\ServiceSubscriberInterface
 */
trait LoggerAwareTrait
{
    use AwareTrait;
    use LoggerTrait;

    private ?LoggerInterface $logger = null;

    #[SubscribedService]
    public function getLogger(): LoggerInterface
    {
        if ($this->logger instanceof LoggerInterface) {
            return $this->logger;
        }

        return $this->logger = $this->getContainerService(__FUNCTION__, LoggerInterface::class);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }
}
