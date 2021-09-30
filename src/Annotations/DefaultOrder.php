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
use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * The default sort annotation.
 *
 * @author Laurent Muller
 *
 * @Annotation
 * @Target("CLASS", "ANNOTATION")
 */
final class DefaultOrder
{
    /**
     * The column name.
     *
     * @Required
     */
    public string $name = '';

    /**
     * The column order.
     *
     * @Enum({"asc", "desc"})
     */
    public string $order = 'asc';
}
