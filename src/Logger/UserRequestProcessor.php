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

use App\Entity\Log;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Log record processor to add user identifier, if any; to extra data.
 */
readonly class UserRequestProcessor implements ProcessorInterface
{
    public function __construct(private Security $security)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $record->extra[Log::USER_FIELD] = $user->getUserIdentifier();
        }

        return $record;
    }
}
