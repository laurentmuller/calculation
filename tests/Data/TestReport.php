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

namespace App\Tests\Data;

use App\Controller\AbstractController;
use App\Report\AbstractReport;

/**
 * Report for tests purpose.
 */
class TestReport extends AbstractReport
{
    public function __construct(AbstractController $controller, private readonly bool $render = true)
    {
        parent::__construct($controller);
    }

    public function render(): bool
    {
        if ($this->render) {
            $this->addPage();
        }

        return $this->render;
    }
}
