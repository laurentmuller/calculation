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

namespace App\Model;

use App\Enums\FlashType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Extends the TranslatableMessage class with flash type.
 */
class TranslatableFlashMessage extends TranslatableMessage
{
    public function __construct(
        string $message,
        array $parameters = [],
        ?string $domain = null,
        private readonly FlashType $type = FlashType::SUCCESS
    ) {
        parent::__construct($message, $parameters, $domain);
    }

    public function getType(): FlashType
    {
        return $this->type;
    }
}
