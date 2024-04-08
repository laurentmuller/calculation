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

use App\Service\CommandService;
use Symfony\Component\Console\Command\Command;

/**
 * Contains result of executing a command.
 *
 * @see CommandService::execute()
 */
readonly class CommandResult
{
    /**
     * @param int    $status  the running status
     * @param string $content the output content
     */
    public function __construct(public int $status, public string $content)
    {
    }

    /**
     * Returns a value indicating if the running status is success.
     */
    public function isSuccess(): bool
    {
        return Command::SUCCESS === $this->status;
    }
}
