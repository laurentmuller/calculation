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

use App\Parameter\HomePageParameter;

/**
 * @extends ParameterTestCase<HomePageParameter>
 */
final class HomePageParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['calculations', 'calculations'];
        yield ['darkNavigation', 'dark_navigation'];
        yield ['panelMonth', 'panel_month'];
        yield ['panelState', 'panel_state'];
        yield ['statusBar', 'status_bar'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['calculations', 12];
        yield ['darkNavigation', true];
        yield ['panelMonth', true];
        yield ['panelState', true];
        yield ['statusBar', true];
    }

    public function testDefaultValue(): void
    {
        self::assertSame(12, $this->parameter->getCalculations());
        self::assertTrue($this->parameter->isDarkNavigation());
        self::assertTrue($this->parameter->isPanelMonth());
        self::assertTrue($this->parameter->isPanelState());
        self::assertTrue($this->parameter->isStatusBar());

        self::assertSame('parameter_home_page', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setCalculations(8);
        self::assertSame(8, $this->parameter->getCalculations());
        $this->parameter->setDarkNavigation(false);
        self::assertFalse($this->parameter->isDarkNavigation());
        $this->parameter->setPanelCatalog(false);
        self::assertFalse($this->parameter->isPanelCatalog());
        $this->parameter->setPanelMonth(false);
        self::assertFalse($this->parameter->isPanelMonth());
        $this->parameter->setPanelState(false);
        self::assertFalse($this->parameter->isPanelState());
        $this->parameter->setStatusBar(false);
        self::assertFalse($this->parameter->isStatusBar());
    }

    #[\Override]
    protected function createParameter(): HomePageParameter
    {
        return new HomePageParameter();
    }
}
