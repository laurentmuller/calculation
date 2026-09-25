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

use Symfony\Component\HttpFoundation\Response;

/**
 * Service to perform HTTP requests using the cURL library.
 */
readonly class CurlService
{
    private \CurlHandle $handle;

    /**
     * @param array<int, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->handle = \curl_init();
        if ([] !== $options) {
            $this->setOptions($options);
        }
    }

    public function __destruct()
    {
        \curl_close($this->handle);
    }

    public function execute(): bool|string
    {
        return \curl_exec($this->handle);
    }

    public function getEffectiveUrl(): string
    {
        return $this->getInfo(\CURLINFO_EFFECTIVE_URL);
    }

    public function getInfo(int $option): mixed
    {
        return \curl_getinfo($this->handle, $option);
    }

    public function getResponseCode(): int
    {
        return $this->getInfo(\CURLINFO_RESPONSE_CODE);
    }

    /**
     * @param array<int, mixed> $options
     */
    public static function instance(array $options = []): self
    {
        return new self($options);
    }

    public function isValidUrl(string $url): bool
    {
        if (false === \filter_var($url, \FILTER_VALIDATE_URL)) {
            return false;
        }

        $this->reset();
        $this->setOptions([
            \CURLOPT_URL => $url,
            \CURLOPT_NOBODY => true,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => true,
        ]);
        $this->execute();

        return Response::HTTP_OK === $this->getResponseCode();
    }

    public function reset(): void
    {
        \curl_reset($this->handle);
    }

    public function setOption(int $option, mixed $value): bool
    {
        return \curl_setopt($this->handle, $option, $value);
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOptions(array $options): bool
    {
        return \curl_setopt_array($this->handle, $options);
    }

    public function setUrl(string $url): self
    {
        $this->setOption(\CURLOPT_URL, $url);

        return $this;
    }
}
