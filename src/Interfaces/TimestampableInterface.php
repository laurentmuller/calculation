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

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Class implementing this interface deals with the creation and update information.
 */
interface TimestampableInterface extends EntityInterface
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
     * Gets the translatable message for the created date and the created user.
     *
     * @param bool $short <code>false</code> to get only data and user; <code>true</code> to get with a label prefix
     */
    public function getCreatedMessage(bool $short = false): TranslatableMessage;

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
     * Gets the translatable message for the updated date and the updated user.
     *
     * @param bool $short <code>false</code> to get only data and user; <code>true</code> to get with a label prefix
     */
    public function getUpdatedMessage(bool $short = false): TranslatableMessage;

    /**
     * Update the created and updated values.
     *
     * @return bool true if the existing values are modified
     */
    public function updateTimestampable(\DateTimeImmutable $date, string $user): bool;
}
