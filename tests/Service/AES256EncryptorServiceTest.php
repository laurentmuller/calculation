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

namespace App\Tests\Service;

use App\Service\AES256EncryptorService;
use PHPUnit\Framework\TestCase;

class AES256EncryptorServiceTest extends TestCase
{
    private AES256EncryptorService $encryptor;

    #[\Override]
    protected function setUp(): void
    {
        $this->encryptor = new AES256EncryptorService('fake-key');
    }

    public function testDecryptInvalid(): void
    {
        $data = 'invalid spaced data';
        $actual = $this->encryptor->decrypt($data);
        self::assertFalse($actual);
    }

    /**
     * @throws \JsonException
     */
    public function testDecryptJsonInvalid(): void
    {
        $data = 'invalid spaced data';
        $actual = $this->encryptor->decryptJson($data);
        self::assertFalse($actual);
    }

    public function testEncryptDecryptInt(): void
    {
        $expected = 145_145;
        $data = $this->encryptor->encrypt((string) $expected);
        self::assertIsString($data);
        $actual = (int) $this->encryptor->decrypt($data);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \JsonException
     */
    public function testEncryptDecryptJson(): void
    {
        $expected = [
            'null' => null,
            'true' => true,
            'false' => false,
            'integer' => 10000,
            'string' => 'string',
        ];
        $data = $this->encryptor->encryptJson($expected);
        self::assertIsString($data);
        $actual = $this->encryptor->decryptJson($data);
        self::assertSame($expected, $actual);
    }

    public function testEncryptDecryptString(): void
    {
        $expected = 'This is a message to encrypt';
        $data = $this->encryptor->encrypt($expected);
        self::assertIsString($data);
        $actual = $this->encryptor->decrypt($data);
        self::assertSame($expected, $actual);
    }
}
