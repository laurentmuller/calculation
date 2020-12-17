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

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a category of prodcuts.
 *
 * @ORM\Table(name="sy_Category")
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 * @UniqueEntity(fields="code", message="category.unique_code")
 */
class Category extends AbstractEntity
{
    /**
     * The unique code.
     *
     * @ORM\Column(type="string", length=30, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @var string
     */
    private $code;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    private $description;

    /**
     * The parent group.
     *
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="categories")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=false)
     *
     * @var ?Group
     */
    private $group;

    /**
     * The list of products that fall into this category.
     *
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="category")
     *
     * @var Collection|Product[]
     */
    private $products;

    /**
     * The list of taks that fall into this category.
     *
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="category")
     *
     * @var Collection|Task[]
     */
    private $tasks;

    /**
     * Constructor.
     */
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
            $product->setCategory($this);
        }

        return $this;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCategory($this);
        }

        return $this;
    }

    /**
     * Gets the number of prodcuts.
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
     * Get code.
     *
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Entity\AbstractEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        return $this->getCode();
    }

    /**
     * Gets the code and the group code.
     */
    public function getFullCode(): ?string
    {
        $code = $this->code;
        if ($parent = $this->getGroupCode()) {
            return \sprintf('%s - %s', $code, $parent);
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
        return $this->group ? $this->group->getCode() : null;
    }

    /**
     * Gets the group identifier.
     */
    public function getGroupId(): ?int
    {
        return $this->group ? $this->group->getId() : null;
    }

    /**
     * Get products.
     *
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Returns if this category contains one or more products.
     *
     * @return bool true if contains products
     */
    public function hasProducts(): bool
    {
        return !$this->products->isEmpty();
    }

    /**
     * Returns if this category contains one or more tasks.
     *
     * @return bool true if contains tasks
     */
    public function hasTasks(): bool
    {
        return !$this->tasks->isEmpty();
    }

    /**
     * Remove a product.
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getCategory() === $this) {
                $task->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode(?string $code): self
    {
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

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

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->code,
            $this->description,
        ];
    }
}
