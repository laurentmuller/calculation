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

namespace App\Parameter;

use App\Entity\GlobalProperty;
use App\Repository\GlobalPropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains application parameters.
 *
 * @extends AbstractParameters<GlobalProperty>
 */
class ApplicationParameters extends AbstractParameters
{
    #[Assert\Valid]
    private ?CustomerParameter $customer = null;

    #[Assert\Valid]
    private ?DateParameter $date = null;

    #[Assert\Valid]
    private ?DefaultParameter $default = null;

    #[Assert\Valid]
    private ?ProductParameter $product = null;

    #[Assert\Valid]
    private ?RightsParameter $rights = null;

    #[Assert\Valid]
    private ?SecurityParameter $security = null;

    public function __construct(
        #[Target('calculation.application')]
        CacheInterface $cache,
        EntityManagerInterface $manager,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
        parent::__construct($cache, $manager);
    }

    public function getCustomer(): CustomerParameter
    {
        return $this->customer ??= $this->getCachedParameter(CustomerParameter::class);
    }

    public function getDate(): DateParameter
    {
        return $this->date ??= $this->getCachedParameter(DateParameter::class);
    }

    public function getDefault(): DefaultParameter
    {
        return $this->default ??= $this->getCachedParameter(DefaultParameter::class);
    }

    public function getDefaultValues(): array
    {
        // the customer and date parameters are omitted because default values are null
        return $this->getParametersDefaultValues(
            DefaultParameter::class,
            DisplayParameter::class,
            HomePageParameter::class,
            MessageParameter::class,
            OptionsParameter::class,
            ProductParameter::class,
            SecurityParameter::class,
        );
    }

    /**
     * Gets the product parameter.
     */
    public function getProduct(): ProductParameter
    {
        return $this->product ??= $this->getCachedParameter(ProductParameter::class);
    }

    public function getRights(): RightsParameter
    {
        return $this->rights ??= $this->getCachedParameter(RightsParameter::class);
    }

    /**
     * Gets the security parameter.
     */
    public function getSecurity(): SecurityParameter
    {
        return $this->security ??= $this->getCachedParameter(SecurityParameter::class);
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function save(): bool
    {
        return $this->saveParameters([
            $this->customer,
            $this->date,
            $this->default,
            $this->display,
            $this->homePage,
            $this->message,
            $this->options,
            $this->product,
            $this->rights,
            $this->security,
        ]);
    }

    protected function createProperty(string $name): GlobalProperty
    {
        return GlobalProperty::instance($name);
    }

    protected function getRepository(): GlobalPropertyRepository
    {
        return $this->manager->getRepository(GlobalProperty::class);
    }

    protected function loadProperties(): array
    {
        return $this->getRepository()
            ->findAll();
    }
}
