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

namespace App\Interfaces;

/**
 * Class implementing this interface deals with the creation and update information.
 */
interface TimestampableInterface
{
    /**
     * Gets the creation date.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Gets the creation username.
     */
    public function getCreatedBy(): ?string;

    /**
     * Gets the entity identifier.
     */
    public function getId(): ?int;

    /**
     * Gets the updated date.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Gets the updated username.
     */
    public function getUpdatedBy(): ?string;

    /**
     * Update the created and updated values.
     *
     * @return bool true if the existing values are modified
     */
    public function updateTimestampable(\DateTimeImmutable $date, string $user): bool;
}
