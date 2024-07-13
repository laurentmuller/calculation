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

use App\Traits\LoggerTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerTraitTest extends TestCase
{
    use LoggerTrait;

    private MockObject&LoggerInterface $logger;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function getLogger(): LoggerInterface
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
        self::assertInstanceOf(LoggerInterface::class, $this->logger);
    }
}
