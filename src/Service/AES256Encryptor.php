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
readonly class AES256Encryptor
{
    private const ENCRYPT_METHOD = 'aes-256-ecb';

    private string $initializationVector;
    private string $passphrase;

    /**
     * @throws \LogicException if this encryption method is not found
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%app_secret%')]
        string $key
    ) {
        $methods = \openssl_get_cipher_methods();
        if (!\in_array(self::ENCRYPT_METHOD, $methods, true)) {
            throw new \LogicException("Unable to find the encryption method '{${self::ENCRYPT_METHOD}}'.");
        }

        $this->passphrase = \md5($key);
        $len = \openssl_cipher_iv_length(self::ENCRYPT_METHOD);
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
            self::ENCRYPT_METHOD,
            $this->passphrase,
            0,
            $this->initializationVector
        );
    }

    public function decryptJson(string $data, bool $assoc = true): mixed
    {
        $decoded = $this->decrypt($data);
        if (!\is_string($decoded)) {
            return false;
        }

        return \json_decode($decoded, $assoc);
    }

    public function encrypt(string $data): string|false
    {
        $encrypted = \openssl_encrypt(
            $data,
            self::ENCRYPT_METHOD,
            $this->passphrase,
            0,
            $this->initializationVector
        );

        if (!\is_string($encrypted)) {
            return false;
        }

        return \base64_encode($encrypted);
    }

    public function encryptJson(mixed $data): string|false
    {
        $encoded = \json_encode($data);
        if (!\is_string($encoded)) {
            return false;
        }

        return $this->encrypt($encoded);
    }
}
