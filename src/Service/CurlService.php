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

    /**
     * @param string[] $urls
     *
     * @return array<string, bool>
     */
    public function checkMultipleUrls(array $urls): array
    {
        $options = [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_NOBODY => true,
            \CURLOPT_TIMEOUT => 5,
            \CURLOPT_CONNECTTIMEOUT => 3,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_MAXREDIRS => 3,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) URL-Checker/1.0',
        ];

        $handlers = [];
        $multiHandle = \curl_multi_init();
        foreach ($urls as $url) {
            if (false === \filter_var($url, \FILTER_VALIDATE_URL)) {
                $handlers[$url] = false;
                continue;
            }
            $ch = \curl_init($url);
            \curl_setopt_array($ch, $options);
            \curl_multi_add_handle($multiHandle, $ch);
            $handlers[$url] = $ch;
        }

        do {
            $status = \curl_multi_exec($multiHandle, $running);
            if ($running > 0) {
                \curl_multi_select($multiHandle);
            }
        } while ($running > 0 && \CURLM_OK === $status);

        $results = [];
        foreach ($handlers as $url => $ch) {
            if (false === $ch) {
                $results[$url] = false;
                continue;
            }
            $code = \curl_getinfo($ch, \CURLINFO_RESPONSE_CODE);
            $results[$url] = Response::HTTP_OK === $code;
            \curl_multi_remove_handle($multiHandle, $ch);
            \curl_close($ch);
        }
        \curl_multi_close($multiHandle);

        return $results;
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
