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

namespace App\Logger;

use Symfony\Component\Security\Core\Security;

/**
 * Request processor to add user identifier to extra data.
 */
class UserRequestProcessor
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Security $security)
    {
    }

    /**
     * @param array{extra: array} $record
     */
    public function __invoke(array $record): array
    {
        if (null !== $user = $this->security->getUser()) {
            $record['extra']['user'] = $user->getUserIdentifier();
        }

        return $record;
    }
}
