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

use App\Interfaces\ComparableInterface;
use App\Interfaces\TimestampableInterface;
use App\Traits\TimestampableTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract entity with code and description properties.
 *
 * @implements ComparableInterface<AbstractCodeEntity>
 */
#[ORM\MappedSuperclass]
abstract class AbstractCodeEntity extends AbstractEntity implements ComparableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /** The unique code. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH, unique: true)]
    protected ?string $code = null;

    /** The description. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    protected ?string $description = null;

    /**
     * Clone this entity.
     *
     * @param ?string $code the new code
     */
    public function clone(?string $code = null): static
    {
        $copy = clone $this;
        if (StringUtils::isString($code)) {
            $copy->setCode($code);
        }

        return $copy;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getCode(), (string) $other->getCode());
    }

    /**
     * Get code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->getCode();
    }

    /**
     * Set code.
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set description.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
