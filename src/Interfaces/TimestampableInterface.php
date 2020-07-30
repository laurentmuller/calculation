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

namespace App\Interfaces;

/**
 * Class implementing this interface deals with the creation and update informations.
 *
 * @author Laurent Muller
 */
interface TimestampableInterface
{
    /**
     * Gets the creation date.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Gets the creation user name.
     */
    public function getCreatedBy(): ?string;

    /**
     * Gets the updated date.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Gets the updated user name.
     */
    public function getUpdatedBy(): ?string;

    /**
     * Sets the creation date.
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self;

    /**
     * Sets the creation user name.
     */
    public function setCreatedBy(string $createdBy): self;

    /**
     * Sets the updated date.
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self;

    /**
     * Sets the updated user name.
     */
    public function setUpdatedBy(string $updatedBy): self;
}
