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

use App\Pdf\Enum\PageSize;

/**
 * Test case for App\Pdf\Enum\PageSize.
 */
class PageSizeTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            ['A3', PageSize::A3()],
            ['A4', PageSize::A4()],
            ['A5', PageSize::A5()],
            ['Legal', PageSize::LEGAL()],
            ['Letter', PageSize::LETTER()],
        ];
    }
}
