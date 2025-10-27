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
        yield ['close', 'message_close'];
        yield ['icon', 'message_icon'];
        yield ['position', 'message_position'];
        yield ['progress', 'message_progress'];
        yield ['subTitle', 'message_sub_title'];
        yield ['timeout', 'message_timeout'];
        yield ['title', 'message_title'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['close', true];
        yield ['icon', true];
        yield ['position', MessagePosition::BOTTOM_RIGHT];
        yield ['progress', 1];
        yield ['subTitle', false];
        yield ['timeout', 4000];
        yield ['title', true];
    }

    public function testDefaultValue(): void
    {
        self::assertTrue($this->parameter->isClose());
        self::assertTrue($this->parameter->isIcon());
        self::assertSame(MessagePosition::BOTTOM_RIGHT, $this->parameter->getPosition());
        self::assertSame(1, $this->parameter->getProgress());
        self::assertFalse($this->parameter->isSubTitle());
        self::assertSame(4000, $this->parameter->getTimeout());
        self::assertTrue($this->parameter->isTitle());

        self::assertSame('parameter_message', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setClose(false);
        self::assertFalse($this->parameter->isClose());
        $this->parameter->setIcon(false);
        self::assertFalse($this->parameter->isIcon());
        $this->parameter->setPosition(MessagePosition::TOP_LEFT);
        self::assertSame(MessagePosition::TOP_LEFT, $this->parameter->getPosition());
        $this->parameter->setProgress(5);
        self::assertSame(5, $this->parameter->getProgress());
        $this->parameter->setSubTitle(true);
        self::assertTrue($this->parameter->isSubTitle());
        $this->parameter->setTimeout(1000);
        self::assertSame(1000, $this->parameter->getTimeout());
        $this->parameter->setTitle(false);
        self::assertFalse($this->parameter->isTitle());
    }

    #[\Override]
    protected function createParameter(): MessageParameter
    {
        return new MessageParameter();
    }
}
