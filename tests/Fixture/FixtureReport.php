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

namespace App\Tests\Fixture;

use App\Interfaces\DocumentHelperInterface;
use App\Report\AbstractReport;

/**
 * Report for test purpose.
 */
class FixtureReport extends AbstractReport
{
    public function __construct(DocumentHelperInterface $helper, private readonly bool $render = true)
    {
        parent::__construct($helper);
    }

    #[\Override]
    public function render(): bool
    {
        if ($this->render) {
            $this->addPage();
        }

        return $this->render;
    }
}
