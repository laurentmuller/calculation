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

use App\Traits\MathTrait;
use App\Traits\SearchTrait;
use App\Util\Utils;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity.
 *
 * @author Laurent Muller
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
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * Magic method called after clone.
     */
    public function __clone()
    {
        $this->id = null;
    }

    public function __toString(): string
    {
        return $this->getDisplay();
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
        return $this->id ? $this->id : null;
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
     * Gets the terms to search in.
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
        if (!Utils::isString($str = \trim((string) $str))) {
            return null;
        }

        return $str;
    }
}
