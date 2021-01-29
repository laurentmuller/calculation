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

namespace App\Form\Group;

use App\Entity\GroupMargin;
use App\Form\AbstractMarginType;

/**
 * Group margin edit type.
 *
 * @author Laurent Muller
 */
class GroupMarginType extends AbstractMarginType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(GroupMargin::class);
    }
}
