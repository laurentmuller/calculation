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

namespace App\Service;

use App\Pdf\PdfLabel;
use App\Utils\FileUtils;
use fpdf\Enums\PdfPageSize;
use fpdf\Enums\PdfUnit;
use fpdf\PdfException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get PDF labels.
 */
readonly class PdfLabelService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/labels.json')]
        private string $file,
        private CacheInterface $cache
    ) {
    }

    /**
     * Gets all labels.
     *
     * @return array<string, PdfLabel> an array where the key is the label's name and the value is the label itself
     *
     * @throws PdfException if the file cannot be decoded
     */
    public function all(): array
    {
        return $this->cache->get('service.labels.all', fn (): array => $this->loadLabels());
    }

    /**
     * Gets the label for the given name.
     *
     * @throws PdfException if labels cannot be loaded or if the label does not exist
     */
    public function get(string $name): PdfLabel
    {
        return $this->all()[$name] ?? throw PdfException::format('Unable to find the label "%s".', $name);
    }

    /**
     * Return a value indicating if the given label's name exists.
     *
     * @throws PdfException if labels cannot be loaded
     */
    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->all());
    }

    /**
     * @return array<string, PdfLabel>
     *
     * @throws PdfException
     */
    private function loadLabels(): array
    {
        try {
            $content = FileUtils::decodeJson($this->file);
            $labels = \array_map($this->mapSource(...), $content);
            $keys = \array_column($labels, 'name');

            return \array_combine($keys, $labels);
        } catch (\Exception $e) {
            throw PdfException::instance(\sprintf('Unable to deserialize the content of the file "%s".', $this->file), $e);
        }
    }

    private function mapSource(array $source): PdfLabel
    {
        return new PdfLabel(
            name: $source['name'],
            cols: $source['cols'],
            rows: $source['rows'],
            width: $source['width'],
            height: $source['height'],
            marginLeft: $source['marginLeft'],
            marginTop: $source['marginTop'],
            spaceWidth: $source['spaceWidth'],
            spaceHeight: $source['spaceHeight'],
            fontSize: $source['fontSize'],
            unit: PdfUnit::from($source['unit']),
            pageSize: PdfPageSize::from($source['pageSize']),
        );
    }
}
