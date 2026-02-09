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

namespace App\Service;

use App\Constant\CacheAttributes;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get application properties.
 */
readonly class ApplicationService
{
    public function __construct(
        private ParameterBagInterface $parameters,
        #[Target(CacheAttributes::CACHE_APPLICATION)]
        private CacheInterface $cache,
    ) {
    }

    public function getDescription(): string
    {
        return $this->getParameter('app_description');
    }

    public function getFullName(): string
    {
        return $this->cache->get(
            'app_full_name',
            fn (): string => \sprintf('%s v%s', $this->getName(), $this->getVersion())
        );
    }

    public function getMailerAddress(): Address
    {
        return $this->cache->get(
            'mailer_address',
            fn (): Address => new Address($this->getMailerEmail(), $this->getMailerName())
        );
    }

    public function getMailerEmail(): string
    {
        return $this->getParameter('mailer_user_email');
    }

    public function getMailerName(): string
    {
        return $this->getParameter('mailer_user_name');
    }

    public function getName(): string
    {
        return $this->getParameter('app_name');
    }

    public function getOwnerCity(): string
    {
        return $this->getParameter('app_owner_city');
    }

    public function getOwnerName(): string
    {
        return $this->getParameter('app_owner_name');
    }

    public function getOwnerUrl(): string
    {
        return $this->getParameter('app_owner_url');
    }

    public function getVersion(): string
    {
        return $this->getParameter('app_version');
    }

    private function getParameter(string $key): string
    {
        /** @phpstan-ignore return.type */
        return $this->cache->get($key, fn (): string => $this->parameters->get($key));
    }
}
