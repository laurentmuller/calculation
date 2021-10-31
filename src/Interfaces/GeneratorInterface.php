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

namespace App\Interfaces;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class implementing this interface generate entities.
 *
 * @author Laurent Muller
 */
interface GeneratorInterface
{
    /**
     * Generate entities.
     *
     * @param int  $count    the number of entities to generate
     * @param bool $simulate true to simulate the generation of entities; false to save entities to the database
     */
    public function generate(int $count, bool $simulate): JsonResponse;
}
