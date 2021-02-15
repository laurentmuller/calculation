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

use App\Pdf\Enum\DrawRectangle;

/**
 * Test case for App\Pdf\Enum\DrawRectangle.
 */
class DrawRectangleTest extends AbstractEnumTest
{
    /**
     * {@inheritDoc}
     */
    public function getEnumerations(): array
    {
        return [
            ['D', DrawRectangle::BORDER()],
            ['FD', DrawRectangle::BOTH()],
            ['F', DrawRectangle::FILL()],
        ];
    }
}
