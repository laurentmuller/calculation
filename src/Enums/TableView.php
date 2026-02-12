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
use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;

/**
 * Table view enumeration.
 *
 * @implements PdfEnumDefaultInterface<TableView>
 * @implements EnumSortableInterface<TableView>
 */
#[ReadableEnum(prefix: 'table_view.', useValueAsDefault: true)]
enum TableView: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /** Show values as cards. */
    #[EnumCase(extras: ['page-size' => 15])]
    case CUSTOM = 'custom';

    /** Show values within a table (default value). */
    #[EnumCase(extras: ['page-size' => 20, PdfEnumDefaultInterface::NAME => true])]
    case TABLE = 'table';

    /**
     * Gets the default page size.
     */
    public function getPageSize(): int
    {
        return $this->getExtraInt('page-size');
    }

    /**
     * @return TableView[]
     */
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::TABLE,
            self::CUSTOM,
        ];
    }
}
