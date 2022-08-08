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
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * Table view enumeration.
 *
 * @implements SortableEnumInterface<TableView>
 */
enum TableView: string implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

    /*
     * Show detailed values.
     */
    #[EnumCase('table_view.card')]
    case CARD = 'card';
    /*
     * Show values as cards.
     */
    #[EnumCase('table_view.custom')]
    case CUSTOM = 'custom';
    /*
     * Show values within a table (default value).
     */
    #[EnumCase('table_view.table', ['default' => true])]
    case TABLE = 'table';

    /**
     * Gets the default page size.
     */
    public function getPageSize(): int
    {
        return match ($this) {
            self::CARD => 5,
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
            TableView::CARD,
       ];
    }
}
