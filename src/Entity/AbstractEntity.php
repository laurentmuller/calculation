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

use App\Traits\MathTrait;
use App\Traits\SearchTrait;
use App\Utils\Utils;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity.
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractEntity
{
    use MathTrait;
    use SearchTrait;

    /**
     * The primary key identifier.
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $id;

    /**
     * Magic method called after clone.
     */
    public function __clone()
    {
        $this->id = null;
    }

    public function __toString(): string
    {
        return (string) $this->getDisplay();
    }

    /**
     * Gets a string used to display in the user interface (UI).
     */
    public function getDisplay(): string
    {
        return (string) ($this->getId() ?: 0);
    }

    /**
     * Get the primary key identifier value.
     *
     * @return int|null the key identifier value or null if is a new entity
     */
    public function getId(): ?int
    {
        return $this->id ? (int) $this->id : null;
    }

    /**
     * Returns if this entity is new.
     *
     * @return bool true if this entity has never been saved to the database
     */
    public function isNew(): bool
    {
        return empty($this->id);
    }

    /**
     * Returns if this entity match the given search term.
     *
     * @param string $query the search term
     *
     * @return bool true if match
     *
     * @see AbstractEntity::getSearchTerms()
     */
    public function match(string $query): bool
    {
        $terms = $this->getSearchTerms();
        foreach ($terms as $term) {
            if (false !== \stripos((string) $term, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the terms to search in.
     *
     * @return string[]
     *
     * @see AbstractEntity::match()
     */
    protected function getSearchTerms(): array
    {
        return [];
    }

    /**
     * Trim the given string.
     *
     * @param string $str the value to trim
     *
     * @return string|null the trimmed string or null if empty
     */
    protected function trim(?string $str): ?string
    {
        if (!Utils::isString($str)) {
            return null;
        }
        if (!Utils::isString($str = \trim($str))) {
            return null;
        }

        return $str;
    }
}
