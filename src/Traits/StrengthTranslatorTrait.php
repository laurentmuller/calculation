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

namespace App\Traits;

use App\Interfaces\StrengthInterface;

/**
 * Trait to translate strength levels.
 *
 * @author Laurent Muller
 */
trait StrengthTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Translate the password strength level.
     *
     * @param int $level the strength level (-1 to 4) to translate
     *
     * @return string the translated level
     */
    public function translateLevel(int $level): string
    {
        if ($level < StrengthInterface::LEVEL_NONE) {
            $level = StrengthInterface::LEVEL_NONE;
        } elseif ($level > StrengthInterface::LEVEL_VERY_STRONG) {
            $level = StrengthInterface::LEVEL_VERY_STRONG;
        }
        $id = StrengthInterface::CHOICE_LEVELS[$level];

        return $this->trans($id);
    }
}
