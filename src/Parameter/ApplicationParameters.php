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

use App\Entity\AbstractProperty;
use App\Entity\GlobalProperty;
use App\Repository\GlobalPropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    private ?DisplayParameter $display = null;

    #[Assert\Valid]
    private ?HomePageParameter $homePage = null;

    #[Assert\Valid]
    private ?MessageParameter $message = null;

    #[Assert\Valid]
    private ?OptionParameter $option = null;

    #[Assert\Valid]
    private ?ProductParameter $product = null;

    #[Assert\Valid]
    private ?SecurityParameter $security = null;

    public function __construct(
        #[Target('calculation.application')]
        CacheInterface $cache,
        EntityManagerInterface $manager,
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

    /**
     * Gets the display parameter.
     */
    public function getDisplay(): DisplayParameter
    {
        return $this->display ??= $this->getCachedParameter(DisplayParameter::class);
    }

    /**
     * Gets the home page parameter.
     */
    public function getHomePage(): HomePageParameter
    {
        return $this->homePage ??= $this->getCachedParameter(HomePageParameter::class);
    }

    /**
     * Gets the message parameter.
     */
    public function getMessage(): MessageParameter
    {
        return $this->message ??= $this->getCachedParameter(MessageParameter::class);
    }

    /**
     * Gets the option parameter.
     */
    public function getOption(): OptionParameter
    {
        return $this->option ??= $this->getCachedParameter(OptionParameter::class);
    }

    /**
     * Gets the product parameter.
     */
    public function getProduct(): ProductParameter
    {
        return $this->product ??= $this->getCachedParameter(ProductParameter::class);
    }

    /**
     * Gets the security parameter.
     */
    public function getSecurity(): SecurityParameter
    {
        return $this->security ??= $this->getCachedParameter(SecurityParameter::class);
    }

    /**
     * Save parameters.
     *
     * @return bool true if one of the parameters has changed
     */
    public function save(): bool
    {
        return $this->saveParameters([
            $this->customer,
            $this->date,
            $this->default,
            $this->display,
            $this->homePage,
            $this->message,
            $this->option,
            $this->product,
            $this->security,
        ]);
    }

    protected function createProperty(string $name): AbstractProperty
    {
        return GlobalProperty::instance($name);
    }

    protected function findProperty(string $name): ?GlobalProperty
    {
        return $this->getRepository()
            ->findOneByName($name);
    }

    protected function getRepository(): GlobalPropertyRepository
    {
        return $this->manager->getRepository(GlobalProperty::class);
    }
}
