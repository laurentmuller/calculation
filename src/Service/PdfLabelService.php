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
use fpdf\PdfException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get PDF labels.
 */
readonly class PdfLabelService
{
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
        try {
            return $this->cache->get('service.labels', fn (): array => $this->loadLabels($file));
        } catch (InvalidArgumentException $e) {
            throw PdfException::instance('Unable to load labels.', $e);
        }
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

    private function createSerializer(): Serializer
    {
        $normalizers = [
            new BackedEnumNormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor()),
            new ArrayDenormalizer(),
        ];
        $encoders = [
            new JsonEncoder(),
        ];

        return new Serializer($normalizers, $encoders);
    }

    /**
     * @return array<string, PdfLabel>
     *
     * @throws PdfException
     */
    private function loadLabels(?string $file = null): array
    {
        $file ??= __DIR__ . '/../../resources/data/labels.json';
        if (!FileUtils::exists($file)) {
            throw PdfException::format('Unable to find the file "%s".', $file);
        }

        $content = FileUtils::readFile($file);
        if ('' === $content) {
            throw PdfException::format('Unable to get content of the file "%s".', $file);
        }

        try {
            $serializer = $this->createSerializer();

            /** @psalm-var PdfLabel[] $array */
            $array = $serializer->deserialize($content, PdfLabel::class . '[]', 'json');

            return \array_reduce(
                $array,
                /** @psalm-param array<string, PdfLabel> $carry  */
                fn (array $carry, PdfLabel $label): array => $carry + [$label->name => $label],
                []
            );
        } catch (\Exception $e) {
            $message = \sprintf('Unable to deserialize the content of the file "%s".', $file);
            throw PdfException::instance($message, $e);
        }
    }
}
