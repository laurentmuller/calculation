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
        // the customer and date parameters are omitted because default values are null
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
        $key = DefaultParameter::getCacheKey();
        /** @psalm-var float $minMargin */
        $minMargin = $values[$key]['minMargin'];
        $values[$key]['minMargin'] = $minMargin * 100.0;

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
     * @psalm-param class-string<TEntity> $class
     *
     * @psalm-return TEntity|null
     */
    private function findEntity(string $class, ?int $id): ?EntityInterface
    {
        if (null === $id) {
            return null;
        }

        return $this->manager->getRepository($class)
            ->find($id);
    }
}
