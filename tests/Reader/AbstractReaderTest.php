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

namespace App\Tests\Reader;

use App\Reader\AbstractReader;
use PHPUnit\Framework\TestCase;

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
     * @phpstan-ignore missingType.generics
     */
    private function getResourceReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            public function __construct()
            {
                /** @psalm-var resource $resource */
                $resource = \fopen(__DIR__ . '/../data/csv/data.csv', 'r');
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
     * @phpstan-ignore missingType.generics
     */
    private function getSplFileInfoReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            public function __construct()
            {
                $file = new \SplFileInfo(__DIR__ . '/../data/csv/data.csv');
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
     * @phpstan-ignore missingType.generics
     */
    private function getStringReader(): AbstractReader
    {
        return new class() extends AbstractReader {
            private bool $first = true;

            public function __construct()
            {
                $file = __DIR__ . '/../data/csv/data.csv';
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
