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
    /** The parent group. */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false)]
    public ?Group $group = null;

    /**
     * The products that belong to this category.
     *
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', fetch: self::EXTRA_LAZY)]
    private Collection $products;

    /**
     * The tasks that belong to this category.
     *
     * @var Collection<int, Task>
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
            $this->products->add($product);
            $product->category = $this;
        }

        return $this;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->category = $this;
        }

        return $this;
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
     * Gets the group code.
     */
    public function getGroupCode(): ?string
    {
        return $this->group?->code;
    }

    /**
     * Gets the group identifier.
     */
    public function getGroupId(): ?int
    {
        return $this->group?->id;
    }

    /**
     * Get products.
     *
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Returns if this category contains no product.
     */
    public function isEmptyProducts(): bool
    {
        return $this->products->isEmpty();
    }

    /**
     * Returns if this category contains no task.
     */
    public function isEmptyTasks(): bool
    {
        return $this->tasks->isEmpty();
    }

    /**
     * Remove a product.
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product) && $product->category === $this) {
            $product->category = null;
        }

        return $this;
    }

    /**
     * Remove a task.
     */
    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task) && $task->category === $this) {
            $task->category = null;
        }

        return $this;
    }

    /**
     * Sets the group.
     */
    public function withGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }
}
