<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Traits;

use App\Security\EntityVoter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait to set or get access rights.
 *
 * @property int[] $EntityCalculation      the rights for calculations.
 * @property int[] $EntityCalculationState the rights for calculation state.
 * @property int[] $EntityGroup            the rights for groups.
 * @property int[] $EntityCategory         the rights for categories.
 * @property int[] $EntityCustomer         the rights for customers.
 * @property int[] $EntityGlobalMargin     the rights for global margins.
 * @property int[] $EntityProduct          the rights for products.
 * @property int[] $EntityUser             the rights for users.
 * @property int[] $EntityTask             the rights for tasks.
 * @property int[] $EntityLog              the rights for logs.
 *
 * @author Laurent Muller
 */
trait RightsTrait
{
    use MathTrait;

    /**
     * The overwritten rights flag.
     *
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    protected bool $overwrite = false;

    /**
     * The rights.
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @var ?int[]
     */
    protected ?array $rights = null;

    /**
     * {@inheritdoc}
     */
    public function __get(string $name): ?array
    {
        if ($this->nameExists($name)) {
            return $this->getEntityRights($name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset(string $name): bool
    {
        return $this->nameExists($name);
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param int[]|null $value
     */
    public function __set(string $name, $value): void
    {
        if ($this->nameExists($name) && \is_array($value)) {
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
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the rights.
     *
     * @psalm-param int[]|null $rights
     */
    public function setRights(?array $rights): self
    {
        $this->rights = empty($rights) || 0 === \array_sum($rights) ? null : $rights;

        return $this;
    }

    /**
     * Gets the rights for the given entity name.
     *
     * @return int[] the rights
     */
    private function getEntityRights(string $entity): array
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE === $offset) {
            return [];
        }

        // get value
        $value = $this->getRights()[$offset];
        if (0 === $value) {
            return [];
        }

        // filter
        return \array_filter(EntityVoter::MASK_ATTRIBUTES, fn (int $attribute): bool => $this->isBitSet($value, $attribute));
    }

    /**
     * Returns if the given property name exists.
     *
     * @param string $name the property name to be tested
     *
     * @return bool true if exists; false otherwise
     */
    private function nameExists(string $name): bool
    {
        return \array_key_exists($name, EntityVoter::ENTITY_OFFSETS);
    }

    /**
     * Sets the rights for the given entity.
     *
     * @param string $entity the entity name
     * @param int[]  $rights the rights to set
     */
    private function setEntityRights(string $entity, array $rights): self
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE !== $offset) {
            // update
            $value = \array_sum($rights);
            $newRights = $this->getRights();
            $newRights[$offset] = $value;

            return $this->setRights($newRights);
        }

        return $this;
    }
}
