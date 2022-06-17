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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Security\EntityVoter;
use Doctrine\ORM\Mapping as ORM;
use Elao\Enum\FlagBag;

/**
 * Trait to set or get access rights.
 *
 * @property ?FlagBag $EntityCalculation      the rights for calculations.
 * @property ?FlagBag $EntityCalculationState the rights for calculation state.
 * @property ?FlagBag $EntityGroup            the rights for groups.
 * @property ?FlagBag $EntityCategory         the rights for categories.
 * @property ?FlagBag $EntityCustomer         the rights for customers.
 * @property ?FlagBag $EntityGlobalMargin     the rights for global margins.
 * @property ?FlagBag $EntityProduct          the rights for products.
 * @property ?FlagBag $EntityUser             the rights for users.
 * @property ?FlagBag $EntityTask             the rights for tasks.
 * @property ?FlagBag $EntityLog              the rights for logs.
 */
trait RightsTrait
{
    use MathTrait;

    /**
     * The overwritten rights flag.
     */
    #[ORM\Column(options: ['default' => false])]
    protected bool $overwrite = false;

    /**
     * The rights.
     *
     * @var ?int[]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $rights = null;

    /**
     * @param string $name the property name
     *
     * @return FlagBag<EntityPermission>|null
     */
    public function __get(string $name): ?FlagBag
    {
        if ($this->entityExists($name)) {
            return $this->getEntityRights($name);
        }

        return null;
    }

    public function __isset(string $name): bool
    {
        return $this->entityExists($name);
    }

    /**
     * @psalm-param string|FlagBag<EntityPermission> $value
     */
    public function __set(string $name, mixed $value): void
    {
        if ($this->entityExists($name) && $value instanceof FlagBag) {
            $this->setEntityRights($name, $value);
        }
    }

    /**
     * Gets the rights.
     *
     * @return int[]
     */
    public function getRights(): array
    {
        return $this->rights ?? EntityVoter::getEmptyRights();
    }

    /**
     * Gets a value indicating if this rights overwrite the default rights.
     *
     * @return bool true if overwritten, false to use the default rights
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Sets a value indicating if this rights overwrite the default rights.
     */
    public function setOverwrite(bool $overwrite): static
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the rights.
     *
     * @psalm-param int[]|null $rights
     */
    public function setRights(?array $rights): static
    {
        $this->rights = empty($rights) || 0 === \array_sum($rights) ? null : $rights;

        return $this;
    }

    /**
     * Returns if the given entity name exists.
     */
    private function entityExists(string $name): bool
    {
        return null !== EntityName::tryFromMixed($name);
    }

    /**
     * Gets the rights for the given entity name.
     *
     * @return FlagBag<EntityPermission>|null
     */
    private function getEntityRights(string $entity): ?FlagBag
    {
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE === $offset) {
            return null;
        }
        $rights = $this->getRights();
        $value = $rights[$offset] ?? 0;

        return new FlagBag(EntityPermission::class, $value);
    }

    /**
     * Sets the rights for the given entity.
     *
     * @param FlagBag<EntityPermission> $rights
     */
    private function setEntityRights(string $entity, FlagBag $rights): static
    {
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE !== $offset) {
            $newRights = $this->getRights();
            $newRights[$offset] = $rights->getValue();

            return $this->setRights($newRights);
        }

        return $this;
    }
}
