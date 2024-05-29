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

namespace App\Pdf;

use fpdf\PdfException;
use fpdf\PdfPageSize;
use fpdf\PdfUnit;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Contains information about an Avery format.
 */
class PdfAveryFormat
{
    /**
     * The number of horizontal labels (columns).
     *
     * @psalm-var positive-int
     */
    public int $cols = 1;
    /**
     * The font size in points.
     *
     * @psalm-var positive-int
     */
    public int $fontSize = 9;
    /**
     * The height of labels.
     */
    public float $height = 0.0;
    /**
     * The left margin.
     */
    public float $marginLeft = 0.0;
    /**
     * The top margin.
     */
    public float $marginTop = 0.0;
    /**
     * The label's name.
     */
    public string $name = '';
    /**
     * The page size.
     */
    public PdfPageSize $pageSize = PdfPageSize::A4;
    /**
     * The number of vertical labels (rows).
     *
     * @psalm-var positive-int
     */
    public int $rows = 1;
    /**
     * The horizontal space between 2 labels.
     */
    public float $spaceX = 0.0;
    /**
     * The vertical space between 2 labels.
     */
    public float $spaceY = 0;
    /**
     * The layout unit.
     */
    public PdfUnit $unit = PdfUnit::MILLIMETER;
    /**
     * The width of labels.
     */
    public float $width = 0;

    /**
     * Gets the horizontal offset for the given column.
     */
    public function getOffsetX(int $column): float
    {
        return $this->marginLeft + (float) $column * ($this->width + $this->spaceX);
    }

    /**
     * Gets the vertical offset for the given row.
     */
    public function getOffsetY(int $row): float
    {
        return $this->marginTop + (float) $row * ($this->height + $this->spaceY);
    }

    /**
     * Decode the content of the given JSON file and return the parsed formats.
     *
     * @param ?string $file the file to decode or null to use default
     *
     * @return array<string, PdfAveryFormat> the Avery formats
     *
     * @throws PdfException if the file cannot be decoded
     */
    public static function loadFormats(?string $file = null): array
    {
        $file ??= __DIR__ . '/../../resources/data/avery.json';
        if (!\file_exists($file)) {
            throw PdfException::format('Unable to find the file "%s".', $file);
        }

        $content = \file_get_contents($file);
        if (!\is_string($content)) {
            throw PdfException::format('Unable to get content of the file "%s".', $file);
        }

        $normalizers = [
            new BackedEnumNormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor()),
            new ArrayDenormalizer(),
        ];
        $encoders = [
            new JsonEncoder(),
        ];
        $serializer = new Serializer($normalizers, $encoders);

        try {
            /** @psalm-var array $array */
            $array = $serializer->deserialize($content, self::class . '[]', 'json');

            return \array_reduce(
                $array,
                /** @psalm-param array<string, PdfAveryFormat> $carry  */
                fn (array $carry, PdfAveryFormat $format): array => $carry + [$format->name => $format],
                []
            );
        } catch (\Exception) {
            throw PdfException::format('Unable to deserialize the content of the file "%s".', $file);
        }
    }

    /**
     * Gets the number of labels per a page.
     */
    public function size(): int
    {
        return $this->cols * $this->rows;
    }

    /**
     * Clone this instance and convert values to millimeters.
     *
     * Returns this instance if this unit is already set as millimeters.
     */
    public function updateLayout(): self
    {
        if (PdfUnit::MILLIMETER === $this->unit) {
            return $this;
        }

        $factor = $this->getScaleFactor();

        $copy = clone $this;
        $copy->marginLeft *= $factor;
        $copy->marginTop *= $factor;
        $copy->spaceX *= $factor;
        $copy->spaceY *= $factor;
        $copy->width *= $factor;
        $copy->height *= $factor;

        return $copy;
    }

    private function getScaleFactor(): float
    {
        return $this->unit->getScaleFactor() / PdfUnit::MILLIMETER->getScaleFactor();
    }
}
