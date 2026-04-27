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

use App\Tests\Fixture\FixtureCountableLogger;
use App\Traits\LoggerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class LoggerTraitTest extends TestCase
{
    use LoggerTrait;

    private FixtureCountableLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = new FixtureCountableLogger();
    }

    #[\Override]
    public function getLogger(): FixtureCountableLogger
    {
        return $this->logger;
    }

    public function testLog(): void
    {
        $this->log(LogLevel::INFO, 'Log');
        $this->logAlert('alert');
        $this->logCritical('critical');
        $this->logDebug('debug');
        $this->logEmergency('emergency');
        $this->logError('error');
        $this->logException(new \Exception());
        $this->logInfo('info');
        $this->logNotice('notice');
        $this->logWarning('warning');
        self::assertCount(10, $this->logger);
    }
}
