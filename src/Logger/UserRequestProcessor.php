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

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Log record processor to add user identifier to extra data.
 */
class UserRequestProcessor implements ProcessorInterface
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (null !== $user = $this->security->getUser()) {
            $record->extra['user'] = $user->getUserIdentifier();
        }

        return $record;
    }
}
