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

use App\Interfaces\EntityInterface;
use App\Traits\MathTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity.
 */
#[ORM\MappedSuperclass]
abstract class AbstractEntity implements EntityInterface
{
    use MathTrait;

    /**
     * The primary key identifier.
     */
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
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

    public function getDisplay(): string
    {
        return \sprintf('%d', (int) $this->id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return empty($this->id);
    }

    /**
     * Trim the given string.
     *
     * @param ?string $str the value to trim
     *
     * @return string|null the trimmed string or null if empty
     */
    protected function trim(?string $str): ?string
    {
        return null === $str || ($str = \trim($str)) === '' ? null : $str;
    }
}
