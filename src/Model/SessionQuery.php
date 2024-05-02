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
 * Class used to save a session value.
 */
class SessionQuery
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $value,
    ) {
    }

    /**
     * Get the JSON decoded value.
     *
     * @throws \JsonException if an error occurs while decoding this value
     */
    public function decode(): mixed
    {
        return \json_decode(json: $this->value, flags: \JSON_THROW_ON_ERROR);
    }
}
