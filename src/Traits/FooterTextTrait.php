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

namespace App\Traits;

/**
 * Trait to get the notification footer text.
 */
trait FooterTextTrait
{
    use TranslatorTrait;

    protected function getFooterText(string $appName, string $appVersion): string
    {
        return $this->trans('notification.footer', [
            '%app_name%' => $appName,
            '%app_version%' => $appVersion,
            ]);
    }
}
