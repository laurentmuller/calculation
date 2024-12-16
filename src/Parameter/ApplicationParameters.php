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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains application parameters.
 *
 * @extends AbstractParameterContainer<GlobalProperty>
 */
class ApplicationParameters extends AbstractParameterContainer
{
    #[Assert\Valid]
    private ?CustomerParameter $customer = null;

    #[Assert\Valid]
    private ?DateParameter $date = null;

    #[Assert\Valid]
    private ?DefaultValueParameter $defaultValue = null;

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

    public function getDefaultValue(): DefaultValueParameter
    {
        return $this->defaultValue ??= $this->getCachedParameter(DefaultValueParameter::class);
    }

    public function getDisplay(): DisplayParameter
    {
        return $this->display ??= $this->getCachedParameter(DisplayParameter::class);
    }

    public function getHomePage(): HomePageParameter
    {
        return $this->homePage ??= $this->getCachedParameter(HomePageParameter::class);
    }

    public function getMessage(): MessageParameter
    {
        return $this->message ??= $this->getCachedParameter(MessageParameter::class);
    }

    public function getOption(): OptionParameter
    {
        return $this->option ??= $this->getCachedParameter(OptionParameter::class);
    }

    public function getProduct(): ProductParameter
    {
        return $this->product ??= $this->getCachedParameter(ProductParameter::class);
    }

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
        $saved = false;
        if ($this->saveParameter($this->customer)) {
            $saved = true;
        }
        if ($this->saveParameter($this->date)) {
            $saved = true;
        }
        if ($this->saveParameter($this->defaultValue)) {
            $saved = true;
        }
        if ($this->saveParameter($this->display)) {
            $saved = true;
        }
        if ($this->saveParameter($this->homePage)) {
            $saved = true;
        }
        if ($this->saveParameter($this->message)) {
            $saved = true;
        }
        if ($this->saveParameter($this->option)) {
            $saved = true;
        }
        if ($this->saveParameter($this->product)) {
            $saved = true;
        }
        if ($this->saveParameter($this->security)) {
            $saved = true;
        }

        return $saved;
    }

    protected function createProperty(string $name): AbstractProperty
    {
        return GlobalProperty::instance($name);
    }

    protected function findProperty(string $name): ?GlobalProperty
    {
        return $this->manager->getRepository(GlobalProperty::class)
            ->findOneByName($name);
    }

    protected function getPropertyClass(): string
    {
        return GlobalProperty::class;
    }
}
