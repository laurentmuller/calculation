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

use App\Tests\KernelServiceTestCase;
use App\Traits\SessionAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(SessionAwareTrait::class)]
class SessionKernelServiceTest extends KernelServiceTestCase
{
    use SessionAwareTrait;

    /**
     * @throws ContainerExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        $session = new Session();
        $request = new Request();
        $request->setSession($session);

        $requestStack = $this->getService(RequestStack::class);
        $requestStack->push($request);
        $this->setRequestStack($requestStack);
    }

    public function testHasSessionValue(): void
    {
        $key = 'value_key';
        $actual = $this->hasSessionValue($key);
        self::assertFalse($actual);

        $this->setSessionValue($key, 'value');
        $actual = $this->hasSessionValue($key);
        self::assertTrue($actual);

        $this->removeSessionValue($key);
        $actual = $this->hasSessionValue($key);
        self::assertFalse($actual);
    }

    public function testRemoveSessionValue(): void
    {
        $key = 'remove_session_value';
        $value = 'value';
        $this->setSessionValue($key, $value);
        $actual = $this->removeSessionValue($key);
        self::assertSame($value, $actual);
    }

    public function testRemoveSessionValues(): void
    {
        $key1 = 'remove_session_value1';
        $key2 = 'remove_session_value2';
        $value1 = 'value1';
        $value2 = 'value2';
        $this->setSessionValue($key1, $value1);
        $this->setSessionValue($key2, $value2);
        $actual = $this->removeSessionValues($key1, $key2);
        self::assertSame([$value1, $value2], $actual);
    }

    public function testSessionBool(): void
    {
        $key = 'bool';
        $actual = $this->isSessionBool($key);
        self::assertFalse($actual);

        $actual = $this->isSessionBool($key, true);
        self::assertTrue($actual);

        $this->setSessionValue($key, false);
        $actual = $this->isSessionBool($key);
        self::assertFalse($actual);

        $actual = $this->isSessionBool($key, true);
        self::assertFalse($actual);
    }

    public function testSessionDate(): void
    {
        $key = 'date';
        $actual = $this->getSessionDate($key);
        self::assertNull($actual);

        $default = new \DateTime('2015-09-31');
        $actual = $this->getSessionDate($key, $default);
        self::assertSame($default, $actual);

        $value = new \DateTime('2001-01-01');
        $this->setSessionValue($key, $value);
        $actual = $this->getSessionDate($key);
        self::assertSame($value, $actual);

        $actual = $this->getSessionDate($key, $default);
        self::assertSame($value, $actual);

        $timestamp = $value->getTimestamp();
        $this->setSessionValue($key, $timestamp);
        $actual = $this->getSessionDate($key);
        self::assertNotNull($actual);
        self::assertSame($timestamp, $actual->getTimestamp());
    }

    public function testSessionFloat(): void
    {
        $key = 'float';
        $actual = $this->getSessionFloat($key, null);
        self::assertNull($actual);

        $default = 1.0;
        $actual = $this->getSessionFloat($key, $default);
        self::assertSame($default, $actual);

        $value = 25.5;
        $this->setSessionValue($key, $value);
        $actual = $this->getSessionFloat($key, null);
        self::assertSame($value, $actual);

        $actual = $this->getSessionFloat($key, $default);
        self::assertSame($value, $actual);
    }

    public function testSessionInt(): void
    {
        $key = 'int';
        $actual = $this->getSessionInt($key, null);
        self::assertNull($actual);

        $default = 1;
        $actual = $this->getSessionInt($key, $default);
        self::assertSame($default, $actual);

        $value = 25;
        $this->setSessionValue($key, $value);
        $actual = $this->getSessionInt($key, null);
        self::assertSame($value, $actual);

        $actual = $this->getSessionInt($key, $default);
        self::assertSame($value, $actual);
    }

    public function testSessionString(): void
    {
        $key = 'string';
        $actual = $this->getSessionString($key);
        self::assertNull($actual);

        $default = 'default';
        $actual = $this->getSessionString($key, $default);
        self::assertSame($default, $actual);

        $value = 'value';
        $this->setSessionValue($key, $value);
        $actual = $this->getSessionString($key);
        self::assertSame($value, $actual);

        $actual = $this->getSessionString($key, $default);
        self::assertSame($value, $actual);
    }

    public function testSetSessionValue(): void
    {
        $key = 'session_value';
        $this->setSessionValue($key, 'value');
        $actual = $this->hasSessionValue($key);
        self::assertTrue($actual);

        $this->setSessionValue($key, null);
        $actual = $this->hasSessionValue($key);
        self::assertFalse($actual);
    }

    public function testSetSessionValues(): void
    {
        $key1 = 'session_value1';
        $key2 = 'session_value2';
        $key3 = 'session_value3';
        $value1 = 'value1';
        $value2 = 'value2';
        $attributes = [
            $key1 => $value1,
            $key2 => $value2,
            $key3 => null,
        ];

        $this->setSessionValues($attributes);
        self::assertTrue($this->hasSessionValue($key1));
        self::assertTrue($this->hasSessionValue($key2));
        self::assertFalse($this->hasSessionValue($key3));
    }
}
