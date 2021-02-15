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

use App\Pdf\Enum\Border;

/**
 * Test case for App\Pdf\Enum\Border.
 */
class BorderTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            [1, Border::ALL()],
            ['B', Border::BOTTOM()],
            [-1, Border::INHERITED()],
            ['L', Border::LEFT()],
            [0, Border::NONE()],
            ['R', Border::RIGHT()],
            ['T', Border::TOP()],
        ];
    }
}
