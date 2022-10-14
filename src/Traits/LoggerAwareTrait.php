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
 */
trait LoggerAwareTrait
{
    use LoggerTrait;

    private ?LoggerInterface $logger = null;

    #[SubscribedService]
    public function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            /** @psalm-var LoggerInterface $result */
            $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);
            $this->logger = $result;
        }

        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }
}
