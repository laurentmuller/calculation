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
use Symfony\Bridge\Twig\Mime\NotificationEmail;

/**
 * Importance type for email notifications.
 */
class ImportanceType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'importance.low' => NotificationEmail::IMPORTANCE_LOW,
            'importance.medium' => NotificationEmail::IMPORTANCE_MEDIUM,
            'importance.high' => NotificationEmail::IMPORTANCE_HIGH,
            'importance.urgent' => NotificationEmail::IMPORTANCE_URGENT,
        ];
    }
}
