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

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a global margin.
 *
 * @ORM\Entity(repositoryClass="App\Repository\GlobalMarginRepository")
 * @ORM\Table(name="sy_GlobalMargin")
 */
class GlobalMargin extends AbstractMargin
{
}
