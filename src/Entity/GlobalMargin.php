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

use App\Interfaces\TimestampableInterface;
use App\Repository\GlobalMarginRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a global margin.
 */
#[ORM\Table(name: 'sy_GlobalMargin')]
#[ORM\Entity(repositoryClass: GlobalMarginRepository::class)]
class GlobalMargin extends AbstractMargin implements TimestampableInterface
{
    use TimestampableTrait;
}
