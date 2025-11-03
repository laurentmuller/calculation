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

namespace App\Enums;

use App\Interfaces\EnumSortableInterface;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The notification email importance enumeration.
 *
 * @implements PdfEnumDefaultInterface<Importance>
 * @implements EnumSortableInterface<Importance>
 */
#[ReadableEnum(prefix: 'importance.', useValueAsDefault: true)]
enum Importance: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /**
     * High importance.
     */
    case HIGH = 'high';

    /**
     * Low importance (default value).
     */
    #[EnumCase(extras: [PdfEnumDefaultInterface::NAME => true])]
    case LOW = 'low';

    /**
     * Medium importance.
     */
    case MEDIUM = 'medium';

    /**
     * Urgente importance.
     */
    case URGENT = 'urgent';

    /**
     * @return Importance[]
     */
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::URGENT,
        ];
    }

    /**
     * Gets the translated title.
     */
    public function translateTitle(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(id: \sprintf('%s_title', $this->getReadable()), locale: $locale);
    }
}
