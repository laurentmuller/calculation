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
use App\Traits\CacheKeyTrait;
use App\Utils\FileUtils;
use fpdf\Enums\PdfPageSize;
use fpdf\Enums\PdfUnit;
use fpdf\PdfException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get PDF labels.
 */
readonly class PdfLabelService
{
    use CacheKeyTrait;

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * Gets all labels.
     *
     * @param ?string $file the file to decode or null to use default
     *
     * @return array<string, PdfLabel> an array where the key is the label's name and the value is the label itself
     *
     * @throws PdfException if the file cannot be decoded
     */
    public function all(?string $file = null): array
    {
        $key = $this->cleanKey(\sprintf('service.labels.%s', $file ?? 'default'));

        return $this->cache->get($key, fn (): array => $this->loadLabels($file));
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
    private function loadLabels(?string $file = null): array
    {
        $file ??= __DIR__ . '/../../resources/data/labels.json';

        try {
            return \array_reduce(
                FileUtils::decodeJson($file),
                fn (array $carry, array $source): array => $carry + $this->mapSource($source),
                []
            );
        } catch (\Exception $e) {
            throw PdfException::instance(\sprintf('Unable to deserialize the content of the file "%s".', $file), $e);
        }
    }

    private function mapSource(array $source): array
    {
        $label = new PdfLabel();
        $label->name = $source['name'];
        $label->pageSize = PdfPageSize::from($source['pageSize']);
        $label->unit = PdfUnit::from($source['unit']);
        $label->marginLeft = $source['marginLeft'];
        $label->marginTop = $source['marginTop'];
        $label->cols = $source['cols'];
        $label->rows = $source['rows'];
        $label->spaceX = $source['spaceX'];
        $label->spaceY = $source['spaceY'];
        $label->width = $source['width'];
        $label->height = $source['height'];
        $label->fontSize = $source['fontSize'];

        return [$label->name => $label];
    }
}
