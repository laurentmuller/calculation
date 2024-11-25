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

namespace App\Tests\Form\GlobalMargin;

use App\Entity\GlobalMargin;
use App\Form\GlobalMargin\GlobalMarginType;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<GlobalMargin, GlobalMarginType>
 */
class GlobalMarginTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'minimum' => 0.0,
            'maximum' => 1.1,
            'margin' => 0.0,
        ];
    }

    protected function getEntityClass(): string
    {
        return GlobalMargin::class;
    }

    protected function getFormTypeClass(): string
    {
        return GlobalMarginType::class;
    }
}
