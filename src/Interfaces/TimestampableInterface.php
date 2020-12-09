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
