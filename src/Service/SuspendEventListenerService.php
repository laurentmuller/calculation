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

namespace App\Service;

use App\Interfaces\DisableListenerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to enable and disable doctrine listeners. Only listeners implementing
 * the <code>DisableListenerInterface</code> interface are taken into account.
 *
 * @author Laurent Muller
 *
 * @see DisableListenerInterface
 */
class SuspendEventListenerService
{
    /**
     * The disabled state.
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * The entity manager.
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $manager the entity manager get listeners
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Destructor. The listeners are automatically enabled.
     */
    public function __destruct()
    {
        $this->enableListeners();
    }

    /**
     * Disable listeners. Do nothing if the listeners are already disabled.
     */
    public function disableListeners(): void
    {
        if (!$this->disabled) {
            $this->updateListeners(false);
            $this->disabled = true;
        }
    }

    /**
     * Enable listeners. Do nothing if the listeners are not disabled.
     */
    public function enableListeners(): void
    {
        if ($this->disabled) {
            $this->updateListeners(true);
            $this->disabled = false;
        }
    }

    /**
     * Returns a value indicating if the listeners are disabled.
     *
     * @return bool true if disabled; false if enabled
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Update listeners enablement.
     *
     * @param bool $enabled true to enable, false to disable
     */
    private function updateListeners(bool $enabled): void
    {
        $manager = $this->manager->getEventManager();

        foreach ($manager->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof DisableListenerInterface) {
                    $listener->setEnabled($enabled);
                }
            }
        }
    }
}
