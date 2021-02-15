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

namespace App\Tests\Enum;

use App\Pdf\Enum\Zoom;

/**
 * Test case for App\Pdf\Enum\Zoom.
 */
class ZoomTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            ['default', Zoom::DEFAULT()],
            ['fullpage', Zoom::FULL_PAGE()],
            ['fullwidth', Zoom::FULL_WIDTH()],
            ['real', Zoom::REAL()],
        ];
    }
}
