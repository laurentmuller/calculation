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

namespace App\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Is used to specify an array of default sort annotations.
 *
 * @author Laurent Muller
 *
 * @Annotation
 * @Target("CLASS")
 */
final class DefaultOrders
{
    /**
     * One or more default order annotations.
     *
     * @var DefaultOrder[]
     */
    public array $value = [];
}
