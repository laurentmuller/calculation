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

/**
 * Class implementing this interface provide entity informations.
 */
interface EntityInterface
{
    /**
     * Gets a string used to display in the user interface (UI).
     */
    public function getDisplay(): string;

    /**
     * Get the primary key identifier value.
     *
     * @return int|null the key identifier value or null if is a new entity
     */
    public function getId(): ?int;

    /**
     * Returns if this entity is new.
     *
     * @return bool true if this entity has never been saved to the database
     */
    public function isNew(): bool;

    /**
     * Returns if this entity match the given search term.
     *
     * @param string $query the search term
     *
     * @return bool true if match
     */
    public function match(string $query): bool;
}
