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

namespace App\Tests\Traits;

use App\Traits\SessionAwareTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class SessionAwareTraitTest extends AwareTraitTestCase
{
    use SessionAwareTrait;

    public function testGetSession(): void
    {
        $expected = new Session();
        $this->initializeRequestStack($expected);
        $actual = $this->getSession();
        self::assertSame($expected, $actual);
    }

    public function testGetSessionDateAsDate(): void
    {
        $date = new DatePoint();
        $session = $this->createMock(Session::class);
        $session->method('get')
            ->willReturn($date);
        $this->initializeRequestStack($session);
        $actual = $this->getSessionDate('date');
        self::assertInstanceOf(DatePoint::class, $actual);
        self::assertSame($date->getTimestamp(), $actual->getTimestamp());
    }

    public function testGetSessionDateAsInt(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')
            ->willReturn(1);
        $this->initializeRequestStack($session);
        $actual = $this->getSessionDate('date');
        self::assertInstanceOf(DatePoint::class, $actual);
        self::assertSame(1, $actual->getTimestamp());
    }

    public function testGetSessionDateNull(): void
    {
        $this->initializeRequestStack();
        $actual = $this->getSessionDate('date');
        self::assertNull($actual);
    }

    public function testGetSessionFloat(): void
    {
        $this->initializeRequestStack();
        $actual = $this->getSessionFloat('key');
        self::assertSame(0.0, $actual);
    }

    public function testGetSessionInt(): void
    {
        $this->initializeRequestStack();
        $actual = $this->getSessionInt('key');
        self::assertSame(0, $actual);
    }

    public function testGetSessionString(): void
    {
        $this->initializeRequestStack();
        $actual = $this->getSessionString('key');
        self::assertNull($actual);
    }

    public function testGetSessionWithException(): void
    {
        $requestStack = $this->initializeRequestStack();
        $requestStack->method('getSession')
            ->willThrowException(new SessionNotFoundException());
        $actual = $this->getSession();
        self::assertNull($actual);
    }

    public function testHasSessionValue(): void
    {
        $this->initializeRequestStack();
        $actual = $this->hasSessionValue('key');
        self::assertFalse($actual);
    }

    public function testIsSessionBool(): void
    {
        $this->initializeRequestStack();
        $actual = $this->isSessionBool('key');
        self::assertFalse($actual);
    }

    public function testRemoveSessionValue(): void
    {
        $this->initializeRequestStack();
        $actual = $this->removeSessionValue('key');
        self::assertNull($actual);
    }

    public function testRemoveSessionValues(): void
    {
        $this->initializeRequestStack();
        $actual = $this->removeSessionValues();
        self::assertSame([], $actual);
    }

    public function testSetSessionValue(): void
    {
        $this->initializeRequestStack();
        $this->setSessionValue('key', 10);
        self::assertNull($this->getSessionString('key'));
    }

    public function testSetSessionValueNull(): void
    {
        $this->initializeRequestStack();
        $this->setSessionValue('key', null);
        self::assertNull($this->getSessionString('key'));
    }

    public function testSetSessionValues(): void
    {
        $this->initializeRequestStack();
        $this->setSessionValues(['key' => 'value']);
        self::assertNull($this->getSessionString('key'));
    }

    private function initializeRequestStack(?Session $session = null): MockObject&RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        if ($session instanceof Session) {
            $requestStack->method('getSession')
                ->willReturn($session);
        }

        $this->setRequestStack($requestStack);

        return $requestStack;
    }
}
