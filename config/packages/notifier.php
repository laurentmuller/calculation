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
use App\Enums\Importance;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $notifier = $config->notifier();
    foreach (Importance::cases() as $importance) {
        $notifier->channelPolicy($importance->value, 'email');
    }
    $notifier->adminRecipient()
        ->email('%mailer_user_email%');
};
