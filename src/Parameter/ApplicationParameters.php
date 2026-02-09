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

use App\Constant\CacheAttributes;
use App\Entity\ApplicationProperty;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Model\CustomerInformation;
use App\Traits\ArrayTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains application parameters.
 *
 * @extends AbstractParameters<ApplicationProperty>
 */
class ApplicationParameters extends AbstractParameters
{
    use ArrayTrait;

    #[Assert\Valid]
    private ?CustomerParameter $customer = null;

    #[Assert\Valid]
    private ?DatesParameter $dates = null;

    #[Assert\Valid]
    private ?DefaultParameter $default = null;

    #[Assert\Valid]
    private ?ProductParameter $product = null;

    #[Assert\Valid]
    private ?RightsParameter $rights = null;

    #[Assert\Valid]
    private ?SecurityParameter $security = null;

    public function __construct(
        #[Target(CacheAttributes::CACHE_PARAMETERS)]
        CacheInterface $cache,
        EntityManagerInterface $manager,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
        parent::__construct($cache, $manager);
    }

    /**
     * Gets the customer parameter.
     */
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

    /**
     * Gets the dates parameter.
     */
    public function getDates(): DatesParameter
    {
        return $this->dates ??= $this->getCachedParameter(DatesParameter::class);
    }

    /**
     * Gets the default parameter.
     */
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
        $values = $this->getParametersDefaultValues([
            DefaultParameter::class,
            DisplayParameter::class,
            HomePageParameter::class,
            MessageParameter::class,
            OptionsParameter::class,
            ProductParameter::class,
            SecurityParameter::class,
        ]);

        // special case for minimum margin
        /** @phpstan-var array<string, array{minMargin: float, ...}> $values */
        $values[DefaultParameter::getCacheKey()]['minMargin'] *= 100.0;

        return $values;
    }

    /**
     * This is a shortcut to get the minimum margin from the default parameter.
     */
    public function getMinMargin(): float
    {
        return $this->getDefault()->getMinMargin();
    }

    /**
     * Gets the product parameter.
     */
    public function getProduct(): ProductParameter
    {
        return $this->product ??= $this->getCachedParameter(ProductParameter::class);
    }

    /**
     * Gets the rights parameter.
     */
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

    /**
     * Gets the debug state.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * This is a shortcut to get the margin below from the default parameter.
     */
    public function isMarginBelow(Calculation|float $value): bool
    {
        return $this->getDefault()->isMarginBelow($value);
    }

    #[\Override]
    protected function createProperty(string $name): ApplicationProperty
    {
        return ApplicationProperty::instance($name);
    }

    #[\Override]
    protected function getParameters(): array
    {
        return parent::getParameters() + [
            CustomerParameter::class => $this->customer,
            DatesParameter::class => $this->dates,
            DefaultParameter::class => $this->default,
            ProductParameter::class => $this->product,
            RightsParameter::class => $this->rights,
            SecurityParameter::class => $this->security,
        ];
    }

    #[\Override]
    protected function loadProperties(): array
    {
        $repository = $this->manager->getRepository(ApplicationProperty::class);

        return $this->mapToKeyValue(
            $repository->findAll(),
            static fn (ApplicationProperty $property): array => [$property->getName() => $property]
        );
    }

    /**
     * @template TEntity of EntityInterface
     *
     * @param class-string<TEntity> $class
     *
     * @return TEntity|null
     */
    private function findEntity(string $class, ?int $id): ?EntityInterface
    {
        return null === $id ? null : $this->manager->getRepository($class)->find($id);
    }
}
