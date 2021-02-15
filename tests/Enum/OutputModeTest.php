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

use App\Pdf\Enum\OutputMode;

/**
 * Test case for App\Pdf\Enum\OutputMode.
 */
class OutputModeTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            ['D', OutputMode::DOWNLOAD()],
            ['F', OutputMode::FILE()],
            ['I', OutputMode::INLINE()],
            ['S', OutputMode::STRING()],
        ];
    }
}
