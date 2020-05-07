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
abstract class BaseEntity implements EntityInterface
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
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return (string) ($this->getId() ?: 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id ? (int) $this->id : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool
    {
        return empty($this->id);
    }

    /**
     * {@inheritdoc}
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
