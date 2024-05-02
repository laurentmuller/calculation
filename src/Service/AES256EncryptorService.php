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

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class for AES-256 encryption.
 */
readonly class AES256EncryptorService
{
    private const CIPHER_METHOD = 'aes-256-ecb';

    private string $initializationVector;
    private string $passphrase;

    /**
     * @throws \LogicException if this cipher method is not found in available cipher methods
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%app_secret%')]
        string $key
    ) {
        $methods = \openssl_get_cipher_methods();
        if (!\in_array(self::CIPHER_METHOD, $methods, true)) {
            throw new \LogicException(\sprintf('Unable to find the cipher method "%s" in available cipher methods.', self::CIPHER_METHOD));
        }

        $this->passphrase = \md5($key);
        $len = \openssl_cipher_iv_length(self::CIPHER_METHOD);
        $this->initializationVector = \substr($key, 0, (int) $len);
    }

    public function decrypt(string $data): string|false
    {
        $decoded = \base64_decode($data, true);
        if (!\is_string($decoded)) {
            return false;
        }

        return \openssl_decrypt(
            $decoded,
            self::CIPHER_METHOD,
            $this->passphrase,
            0,
            $this->initializationVector
        );
    }

    /**
     * @throws \JsonException if the data cannot be decoded
     */
    public function decryptJson(string $data, bool $assoc = true, int $flags = 0): mixed
    {
        $decoded = $this->decrypt($data);
        if (!\is_string($decoded)) {
            return false;
        }

        return \json_decode($decoded, $assoc, flags: $flags | \JSON_THROW_ON_ERROR);
    }

    public function encrypt(string $data): string|false
    {
        $encrypted = \openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $this->passphrase,
            0,
            $this->initializationVector
        );

        if (!\is_string($encrypted)) {
            return false;
        }

        return \base64_encode($encrypted);
    }

    /**
     * @throws \JsonException if the data cannot be encoded
     */
    public function encryptJson(mixed $data, int $flags = 0): string|false
    {
        $encoded = \json_encode($data, $flags | \JSON_THROW_ON_ERROR);

        return $this->encrypt($encoded);
    }
}
