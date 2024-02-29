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

namespace App\Tests\Utils;

use App\Utils\AbstractReader;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(AbstractReader::class)]
class AbstractReaderTest extends TestCase
{
    public function testResourceReader(): void
    {
        $reader = $this->getResourceReader();
        self::assertTrue($reader->isOpen());
        foreach ($reader as $data) {
            self::assertNull($data);
        }
        $reader->close();
        self::assertFalse($reader->isOpen());
    }

    public function testSplFileInfoReader(): void
    {
        $reader = $this->getSplFileInfoReader();
        self::assertTrue($reader->isOpen());
        foreach ($reader as $data) {
            self::assertNull($data);
        }
        $reader->close();
        self::assertFalse($reader->isOpen());
    }

    public function testStringReader(): void
    {
        $reader = $this->getStringReader();
        self::assertTrue($reader->isOpen());
        foreach ($reader as $data) {
            self::assertIsArray($data);
        }
        $reader->close();
        self::assertFalse($reader->isOpen());
    }

    /**
     * @psalm-suppress MissingTemplateParam
     *
     * @phpstan-ignore-next-line
     */
    private function getResourceReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            public function __construct()
            {
                /** @psalm-var resource $resource */
                $resource = \fopen(__DIR__ . '/../Data/csv_reader.csv', 'r');
                parent::__construct($resource);
            }

            protected function getNextData($stream): null
            {
                return null;
            }
        };
    }

    /**
     * @psalm-suppress MissingTemplateParam
     *
     * @phpstan-ignore-next-line
     */
    private function getSplFileInfoReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            public function __construct()
            {
                $file = new \SplFileInfo(__DIR__ . '/../Data/csv_reader.csv');
                parent::__construct($file);
            }

            protected function getNextData($stream): null
            {
                return null;
            }
        };
    }

    /**
     * @psalm-suppress MissingTemplateParam
     *
     * @phpstan-ignore-next-line
     */
    private function getStringReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            private bool $first = true;

            public function __construct()
            {
                $file = __DIR__ . '/../Data/csv_reader.csv';
                parent::__construct($file);
            }

            protected function getNextData($stream): ?array
            {
                if ($this->first) {
                    $this->first = false;

                    return ['a', 'b'];
                }

                return null;
            }
        };
    }
}
