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

namespace App\Table;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains extra data query parameters.
 */
readonly class DataParams
{
    public function __construct(
        /** The group identifier. */
        #[Assert\GreaterThanOrEqual(0)]
        public int $groupId = 0,
        /** The category identifier. */
        #[Assert\GreaterThanOrEqual(0)]
        public int $categoryId = 0,
        /** The calculation state identifier. */
        #[Assert\GreaterThanOrEqual(0)]
        public int $stateId = 0,
        /** The edit state identifier. */
        #[Assert\Range(min: -1, max: 1)]
        public int $stateEditable = 0,
        /** The log level. */
        #[Assert\NotNull]
        public string $level = '',
        /** The log channel. */
        #[Assert\NotNull]
        public string $channel = '',
        /** The search entity. */
        #[Assert\NotNull]
        public string $entity = '',
        /** The search type. */
        #[Assert\NotNull]
        public string $type = '',
    ) {
    }
}
