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

use App\Interfaces\DefaultEnumInterface;
use App\Interfaces\SortableEnumInterface;
use App\Traits\DefaultEnumTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * Table view enumeration.
 *
 * @implements SortableEnumInterface<TableView>
 */
#[ReadableEnum(prefix: 'table_view.', useValueAsDefault: true)]
enum TableView: string implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

    /*
     * Show values as cards.
     */
    case CUSTOM = 'custom';
    /*
     * Show values within a table (default value).
     */
    #[EnumCase(extras: ['default' => true])]
    case TABLE = 'table';

    /**
     * Gets the default page size.
     */
    public function getPageSize(): int
    {
        return match ($this) {
            self::CUSTOM => 15,
            self::TABLE => 20
        };
    }

    /**
     * @return TableView[]
     */
    public static function sorted(): array
    {
        return [
            TableView::TABLE,
            TableView::CUSTOM,
       ];
    }
}
