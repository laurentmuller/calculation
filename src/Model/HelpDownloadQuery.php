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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class to hold download help image data.
 */
readonly class HelpDownloadQuery
{
    public function __construct(
        #[Assert\PositiveOrZero]
        public int $index,
        #[Assert\NotBlank]
        public string $location,
        #[Assert\NotBlank]
        public string $image
    ) {
    }
}
