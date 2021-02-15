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

use App\Pdf\Enum\MoveLine;

/**
 * Test case for App\Pdf\Enum\MoveLine.
 */
class MoveLineTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            [2, MoveLine::BELOW()],
            [1, MoveLine::NEW_LINE()],
            [0, MoveLine::RIGHT()],
        ];
    }
}
