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

namespace App\Form\Type;

use App\Form\AbstractChoiceType;

/**
 * A Yes/No (translated) choice type.
 *
 * @author Laurent Muller
 */
class YesNoType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'common.value_true' => true,
            'common.value_false' => false,
        ];
    }
}
