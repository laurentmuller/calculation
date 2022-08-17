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

use Psr\Container\ContainerExceptionInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends translator trait wih the subscribed service.
 */
trait TranslatorAwareTrait
{
    use TranslatorTrait;

    /**
     * {@inheritDoc}
     *
     * @throws ContainerExceptionInterface
     */
    #[SubscribedService]
    public function getTranslator(): TranslatorInterface
    {
        /** @psalm-var TranslatorInterface $result */
        $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);

        return $result;
    }
}
