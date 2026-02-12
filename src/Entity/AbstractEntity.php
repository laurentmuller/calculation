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
abstract class AbstractEntity implements \Stringable, EntityInterface
{
    use MathTrait;

    /** The primary key identifier. */
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    public function __clone()
    {
        $this->id = null;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getDisplay();
    }

    #[\Override]
    public function getDisplay(): string
    {
        return \sprintf('%d', $this->id ?? 0);
    }

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function isNew(): bool
    {
        return null === $this->id || 0 === $this->id;
    }
}
