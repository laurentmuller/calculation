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

namespace App\Listener;

use App\Interfaces\IFlashMessageInterface;
use App\Traits\TranslatorFlashMessageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract event listener.
 *
 * @author Laurent Muller
 */
abstract class AbstractListener implements IFlashMessageInterface
{
    use TranslatorFlashMessageTrait;

    /**
     * The container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     */
    protected function __construct(ContainerInterface $container, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->session = $session;
        $this->translator = $translator;
    }
}
