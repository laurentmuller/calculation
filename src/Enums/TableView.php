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

use App\Interfaces\EnumDefaultInterface;
use App\Interfaces\EnumSortableInterface;
use App\Interfaces\EnumTranslatableInterface;
use App\Traits\EnumDefaultTrait;
use App\Traits\EnumTranslatableTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;

/**
 * Table view enumeration.
 *
 * @implements EnumDefaultInterface<TableView>
 * @implements EnumSortableInterface<TableView>
 */
#[ReadableEnum(prefix: 'table_view.', useValueAsDefault: true)]
enum TableView: string implements EnumDefaultInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumDefaultTrait;
    use EnumTranslatableTrait;

    /*
     * Show values as cards.
     */
    #[EnumCase(extras: ['page-size' => 15])]
    case CUSTOM = 'custom';

    /*
     * Show values within a table (default value).
     */
    #[EnumCase(extras: ['page-size' => 20, EnumDefaultInterface::NAME => true])]
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
    public static function sorted(): array
    {
        return [
            self::TABLE,
            self::CUSTOM,
       ];
    }
}
