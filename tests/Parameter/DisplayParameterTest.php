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

use App\Enums\EntityAction;
use App\Enums\TableView;
use App\Parameter\DisplayParameter;

/**
 * @extends ParameterTestCase<DisplayParameter>
 */
final class DisplayParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['displayMode', 'display_mode'];
        yield ['editAction', 'edit_action'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['displayMode', TableView::TABLE];
        yield ['editAction', EntityAction::EDIT];
    }

    public function testDefaultValue(): void
    {
        self::assertSame(TableView::TABLE, $this->parameter->getDisplayMode());
        self::assertSame(EntityAction::EDIT, $this->parameter->getEditAction());

        self::assertSame('parameter_display', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setDisplayMode(TableView::CUSTOM);
        self::assertSame(TableView::CUSTOM, $this->parameter->getDisplayMode());
        $this->parameter->setEditAction(EntityAction::SHOW);
        self::assertSame(EntityAction::SHOW, $this->parameter->getEditAction());
    }

    #[\Override]
    protected function createParameter(): DisplayParameter
    {
        return new DisplayParameter();
    }
}
