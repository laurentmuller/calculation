<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Traits;

/**
 * Trait to add flash messages.
 *
 * @author Laurent Muller
 */
trait FlashMessageTrait
{
    use SessionTrait;

    /**
     * Adds a flash message with the given type to the current session.
     *
     * @param string $type    the message type
     * @param string $message the message content
     */
    protected function addFlashMessage(string $type, string $message): self
    {
        if ($session = $this->doGetSession()) {
            $flashBag = $session->getFlashBag();
            $flashBag->add($type, $message);
        }

        return $this;
    }

    /**
     * Add an error message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message The message to add
     */
    protected function error(string $message): self
    {
        return $this->addFlashMessage('danger', $message);
    }

    /**
     * Add an information message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function info(string $message): self
    {
        return $this->addFlashMessage('info', $message);
    }

    /**
     * Add a success message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function succes(string $message): self
    {
        return $this->addFlashMessage('success', $message);
    }

    /**
     * Add a warning message to the session flash bag.
     * Do nothing if the session is not set.
     *
     * @param string $message the message to add
     */
    protected function warning(string $message): self
    {
        return $this->addFlashMessage('warning', $message);
    }
}
