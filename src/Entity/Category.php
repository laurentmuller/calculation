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

namespace App\Entity;

use App\Repository\CategoryRepository;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a category of products and tasks.
 */
#[ORM\Table(name: 'sy_Category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_category_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'category.unique_code')]
class Category extends AbstractCodeEntity
{
    /**
     * The parent group.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false)]
    private ?Group $group = null;

    /**
     * The products that belong to this category.
     *
     * @var Collection<int, Product>
     *
     * @psalm-var ArrayCollection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', fetch: self::EXTRA_LAZY)]
    private Collection $products;

    /**
     * The tasks that belong to this category.
     *
     * @var Collection<int, Task>
     *
     * @psalm-var ArrayCollection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'category', fetch: self::EXTRA_LAZY)]
    private Collection $tasks;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    /**
     * Add a product.
     */
    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setCategory($this);
        }

        return $this;
    }

    /**
     * @psalm-api
     */
    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCategory($this);
        }

        return $this;
    }

    /**
     * Clone this category.
     *
     * @param ?string $code the new code
     */
    public function clone(?string $code = null): self
    {
        $copy = clone $this;
        if (StringUtils::isString($code)) {
            $copy->setCode($code);
        }

        return $copy;
    }

    /**
     * Gets the number of products and tasks.
     */
    public function countItems(): int
    {
        return $this->countProducts() + $this->countTasks();
    }

    /**
     * Gets the number of products.
     */
    public function countProducts(): int
    {
        return $this->products->count();
    }

    /**
     * Gets the number of tasks.
     */
    public function countTasks(): int
    {
        return $this->tasks->count();
    }

    /**
     * Gets this code and the group code (if any).
     *
     * @psalm-api
     */
    public function getFullCode(): ?string
    {
        $code = $this->code;
        $groupCode = $this->getGroupCode();
        if (null !== $groupCode) {
            return \sprintf('%s - %s', (string) $code, $groupCode);
        }

        return $code;
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * Gets the group code.
     */
    public function getGroupCode(): ?string
    {
        return $this->group?->getCode();
    }

    /**
     * Gets the group identifier.
     */
    public function getGroupId(): ?int
    {
        return $this->group?->getId();
    }

    /**
     * Get products.
     *
     * @return Collection<int, Product>
     *
     * @psalm-api
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @return Collection<int, Task>
     *
     * @psalm-api
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Returns if this category contains one or more products.
     */
    public function hasProducts(): bool
    {
        return 0 !== $this->products->count();
    }

    /**
     * Returns if this category contains one or more tasks.
     */
    public function hasTasks(): bool
    {
        return 0 !== $this->tasks->count();
    }

    /**
     * Remove a product.
     *
     * @psalm-api
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product) && $product->getCategory() === $this) {
            $product->setCategory(null);
        }

        return $this;
    }

    /**
     * Remove a task.
     *
     * @psalm-api
     */
    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task) && $task->getCategory() === $this) {
            $task->setCategory(null);
        }

        return $this;
    }

    /**
     * Sets the group.
     */
    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }
}
