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

namespace App\Tests\Parameter;

use App\Enums\MessagePosition;
use App\Parameter\MessageParameter;

/**
 * @extends ParameterTestCase<MessageParameter>
 */
final class MessageParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['icon', 'message_icon'];
        yield ['close', 'message_close'];
        yield ['title', 'message_title'];
        yield ['subTitle', 'message_sub_title'];
        yield ['progress', 'message_progress'];
        yield ['timeout', 'message_timeout'];
        yield ['position', 'message_position'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['icon', true];
        yield ['close', true];
        yield ['title', true];
        yield ['subTitle', false];
        yield ['progress', 1];
        yield ['timeout', 4000];
        yield ['position', MessagePosition::BOTTOM_RIGHT];
    }

    public function testDefaultValue(): void
    {
        self::assertTrue($this->parameter->isIcon());
        self::assertTrue($this->parameter->isTitle());
        self::assertTrue($this->parameter->isClose());
        self::assertFalse($this->parameter->isSubTitle());
        self::assertSame(1, $this->parameter->getProgress());
        self::assertSame(4000, $this->parameter->getTimeout());
        self::assertSame(MessagePosition::BOTTOM_RIGHT, $this->parameter->getPosition());

        self::assertSame('parameter_message', $this->parameter::getCacheKey());
    }

    public function testGetAttributes(): void
    {
        $actual = $this->parameter->getAttributes();
        self::assertTrue($actual['icon']);
        self::assertTrue($actual['display-close']);
        self::assertTrue($actual['title']);
        self::assertFalse($actual['display-subtitle']);
        self::assertSame(1, $actual['progress']);
        self::assertSame(4000, $actual['timeout']);
        self::assertSame('bottom-right', $actual['position']);
    }

    public function testSetValue(): void
    {
        $this->parameter->setIcon(false);
        self::assertFalse($this->parameter->isIcon());

        $this->parameter->setTitle(false);
        self::assertFalse($this->parameter->isTitle());

        $this->parameter->setClose(false);
        self::assertFalse($this->parameter->isClose());

        $this->parameter->setSubTitle(true);
        self::assertTrue($this->parameter->isSubTitle());

        $this->parameter->setProgress(5);
        self::assertSame(5, $this->parameter->getProgress());

        $this->parameter->setTimeout(1000);
        self::assertSame(1000, $this->parameter->getTimeout());

        $this->parameter->setPosition(MessagePosition::TOP_LEFT);
        self::assertSame(MessagePosition::TOP_LEFT, $this->parameter->getPosition());
    }

    #[\Override]
    protected function createParameter(): MessageParameter
    {
        return new MessageParameter();
    }
}
