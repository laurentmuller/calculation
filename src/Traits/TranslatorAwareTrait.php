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

use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends translator trait wih the subscribed service.
 *
 * @property \Psr\Container\ContainerInterface $container
 */
trait TranslatorAwareTrait
{
    use TranslatorTrait;

    private ?TranslatorInterface $translator = null;

    /**
     * @psalm-suppress all
     */
    #[SubscribedService]
    public function getTranslator(): TranslatorInterface
    {
        if (null === $this->translator) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->translator = $this->container->get(self::class . '::' . __FUNCTION__);
        }

        return $this->translator;
    }

    public function setTranslator(?TranslatorInterface $translator): static
    {
        $this->translator = $translator;

        return $this;
    }
}
