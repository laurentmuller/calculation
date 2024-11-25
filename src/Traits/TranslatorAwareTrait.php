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
 * Extends translator trait within the subscribed service.
 *
 * @psalm-require-implements \Symfony\Contracts\Service\ServiceSubscriberInterface
 */
trait TranslatorAwareTrait
{
    use AwareTrait;
    use TranslatorTrait;

    private ?TranslatorInterface $translator = null;

    #[SubscribedService]
    public function getTranslator(): TranslatorInterface
    {
        if ($this->translator instanceof TranslatorInterface) {
            return $this->translator;
        }

        return $this->translator = $this->getContainerService(__FUNCTION__, TranslatorInterface::class);
    }

    public function setTranslator(TranslatorInterface $translator): static
    {
        $this->translator = $translator;

        return $this;
    }
}
