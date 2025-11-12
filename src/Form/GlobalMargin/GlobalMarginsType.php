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

namespace App\Form\GlobalMargin;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Model\GlobalMargins;

/**
 * Type to edit all global margins.
 *
 * @extends AbstractHelperType<GlobalMargins>
 */
class GlobalMarginsType extends AbstractHelperType
{
    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('margins')->addCollectionType(GlobalMarginType::class);
    }
}
