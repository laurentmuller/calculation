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

use App\Service\AES256Encryptor;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(AES256Encryptor::class)]
class AES256EncryptorTest extends TestCase
{
    private AES256Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new AES256Encryptor('fake-key');
    }

    public function testEncrypt(): void
    {
        $expected = 'This is a message to encrypt';
        $data = $this->encryptor->encrypt($expected);
        self::assertIsString($data);
        $actual = $this->encryptor->decrypt($data);
        self::assertSame($expected, $actual);
    }

    public function testEncryptJson(): void
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
}
