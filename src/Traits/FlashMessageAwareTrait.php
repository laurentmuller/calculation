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

namespace App\Traits;

use App\Enums\FlashType;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Trait to add flash messages.
 */
trait FlashMessageAwareTrait
{
    use SessionAwareTrait;

    /**
     * Adds a flash message with the given type to the current session.
     *
     * @param FlashType|string $type    the message type
     * @param string           $message the message content
     */
    protected function addFlashMessage(FlashType|string $type, string $message): static
    {
        $session = $this->getSession();
        if ($session instanceof Session) {
            if ($type instanceof FlashType) {
                $type = $type->value;
            }
            $session->getFlashBag()->add($type, $message);
        }

        return $this;
    }

    /**
     * Add an error message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message The message to add
     */
    protected function error(string $message): static
    {
        return $this->addFlashMessage(FlashType::DANGER, $message);
    }

    /**
     * Add an information message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function info(string $message): static
    {
        return $this->addFlashMessage(FlashType::INFO, $message);
    }

    /**
     * Add a success message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function success(string $message): static
    {
        return $this->addFlashMessage(FlashType::SUCCESS, $message);
    }

    /**
     * Add a warning message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function warning(string $message): static
    {
        return $this->addFlashMessage(FlashType::WARNING, $message);
    }
}
