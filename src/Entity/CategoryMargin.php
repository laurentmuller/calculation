<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a margin within a category.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CategoryMarginRepository")
 * @ORM\Table(name="sy_CategoryMargin")
 */
class CategoryMargin extends AbstractMargin
{
    /**
     * The parent's category.
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="margins")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false)
     *
     * @var \App\Entity\Category
     */
    protected $category;

    /**
     * Get category.
     *
     * @return \App\Entity\Category
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Set category.
     *
     * @param \App\Entity\Category $category
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
