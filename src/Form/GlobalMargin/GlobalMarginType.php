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

namespace App\Form\GlobalMargin;

use App\Entity\GlobalMargin;
use App\Form\AbstractMarginType;

/**
 * Global margin edit type.
 *
 * @template-extends AbstractMarginType<GlobalMargin>
 */
class GlobalMarginType extends AbstractMarginType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(GlobalMargin::class);
    }
}
