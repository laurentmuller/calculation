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

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @psalm-require-extends KernelTestCase
 */
trait SessionHelperTrait
{
    protected function getSession(KernelBrowser $client): SessionInterface
    {
        /** @psalm-var SessionFactory $factory */
        $factory = static::getContainer()->get('session.factory');
        $session = $factory->createSession();
        $cookieJar = $client->getCookieJar();

        $cookie = $cookieJar->get('MOCKSESSID');
        if ($cookie instanceof Cookie) {
            $session->setId($cookie->getValue());
            $session->start();

            return $session;
        }

        $session->start();
        $session->save();
        $cookie = new Cookie(
            name: $session->getName(),
            value: $session->getId(),
            domain: 'localhost',
        );
        $cookieJar->set($cookie);

        return $session;
    }
}
