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

namespace App\Parameter;

use App\Attribute\Parameter;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Home page parameter.
 */
class HomePageParameter implements ParameterInterface
{
    #[Assert\Choice([4, 8, 12, 16, 20])]
    #[Parameter('calculations', 12)]
    private int $calculations = 12;

    #[Parameter('dark_navigation', true)]
    private bool $darkNavigation = true;

    #[Parameter('panel_month', true)]
    private bool $panelMonth = true;

    #[Parameter('panel_state', true)]
    private bool $panelState = true;

    #[Parameter('status_bar', true)]
    private bool $statusBar = true;

    public static function getCacheKey(): string
    {
        return 'parameter_home_page';
    }

    public function getCalculations(): int
    {
        return $this->calculations;
    }

    public function isDarkNavigation(): bool
    {
        return $this->darkNavigation;
    }

    public function isPanelMonth(): bool
    {
        return $this->panelMonth;
    }

    public function isPanelState(): bool
    {
        return $this->panelState;
    }

    public function isStatusBar(): bool
    {
        return $this->statusBar;
    }

    public function setCalculations(int $calculations): self
    {
        $this->calculations = $calculations;

        return $this;
    }

    public function setDarkNavigation(bool $darkNavigation): self
    {
        $this->darkNavigation = $darkNavigation;

        return $this;
    }

    public function setPanelMonth(bool $panelMonth): self
    {
        $this->panelMonth = $panelMonth;

        return $this;
    }

    public function setPanelState(bool $panelState): self
    {
        $this->panelState = $panelState;

        return $this;
    }

    public function setStatusBar(bool $statusBar): self
    {
        $this->statusBar = $statusBar;

        return $this;
    }
}
