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

namespace App\Reader;

use App\Service\CsvService;

/**
 * Class to read CSV file on the fly.
 *
 * @extends AbstractReader<string[]>
 */
class CsvReader extends AbstractReader
{
    /**
     * @param string|resource $file    the CSV file to open or an opened resource
     * @param CsvService      $service the service to parse content
     */
    public function __construct(mixed $file, private readonly CsvService $service)
    {
        parent::__construct($file);
    }

    /**
     * Creates a new instance.
     *
     * @param string|resource $file    the CSV file to open or an opened resource
     * @param CsvService      $service the service to parse content
     */
    public static function instance(mixed $file, CsvService $service = new CsvService()): self
    {
        return new self($file, $service);
    }

    #[\Override]
    protected function nextData($stream): ?array
    {
        return $this->service->parse($stream);
    }
}
