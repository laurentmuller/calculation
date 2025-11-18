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

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalProperty;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Model\CustomerInformation;
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

    /**
     * Gets the customer information.
     *
     * @param ?bool $printAddress an optional value indicating if the address is printed
     */
    public function getCustomerInformation(?bool $printAddress = null): CustomerInformation
    {
        $printAddress ??= $this->getOptions()->isPrintAddress();

        return $this->getCustomer()
            ->getCustomerInformation($printAddress);
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
     * Gets the default category.
     */
    public function getDefaultCategory(): ?Category
    {
        return $this->findEntity(Category::class, $this->getDefault()->getCategoryId());
    }

    /**
     * Gets the default product.
     */
    public function getDefaultProduct(): ?Product
    {
        return $this->findEntity(Product::class, $this->getProduct()->getProductId());
    }

    /**
     * Gets the default calculation state.
     */
    public function getDefaultState(): ?CalculationState
    {
        return $this->findEntity(CalculationState::class, $this->getDefault()->getStateId());
    }

    #[\Override]
    public function getDefaultValues(): array
    {
        // the customer and date parameters are omitted because all default values are null
        $values = $this->getParametersDefaultValues(
            DefaultParameter::class,
            DisplayParameter::class,
            HomePageParameter::class,
            MessageParameter::class,
            OptionsParameter::class,
            ProductParameter::class,
            SecurityParameter::class,
        );

        // special case for minimum margin
        /** @phpstan-var array<string, array{minMargin: float, ...}> $values */
        $values[DefaultParameter::getCacheKey()]['minMargin'] *= 100.0;

        return $values;
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

    #[\Override]
    public function save(): bool
    {
        return $this->saveParameters([
            DisplayParameter::class => $this->display,
            HomePageParameter::class => $this->homePage,
            MessageParameter::class => $this->message,
            OptionsParameter::class => $this->options,

            CustomerParameter::class => $this->customer,
            DateParameter::class => $this->date,
            DefaultParameter::class => $this->default,
            ProductParameter::class => $this->product,
            RightsParameter::class => $this->rights,
            SecurityParameter::class => $this->security,
        ]);
    }

    #[\Override]
    protected function createProperty(string $name): GlobalProperty
    {
        return GlobalProperty::instance($name);
    }

    #[\Override]
    protected function getRepository(): GlobalPropertyRepository
    {
        return $this->manager->getRepository(GlobalProperty::class);
    }

    #[\Override]
    protected function loadProperties(): array
    {
        return $this->getRepository()
            ->findAll();
    }

    /**
     * @template TEntity of EntityInterface
     *
     * @phpstan-param class-string<TEntity> $class
     *
     * @phpstan-return TEntity|null
     */
    private function findEntity(string $class, ?int $id): ?EntityInterface
    {
        return null === $id ? null : $this->manager->getRepository($class)->find($id);
    }
}
