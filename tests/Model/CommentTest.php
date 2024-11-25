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

namespace App\Tests\Model;

use App\Entity\User;
use App\Enums\Importance;
use App\Model\Comment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;

class CommentTest extends TestCase
{
    public function testConstructor(): void
    {
        $comment = new Comment();
        self::assertTrue($comment->isMail());
        $comment = new Comment(false);
        self::assertFalse($comment->isMail());

        self::assertSame([], $comment->getAttachments());
        self::assertNull($comment->getFromAddress());
        self::assertSame(Importance::getDefault(), $comment->getImportance());
        self::assertNull($comment->getMessage());
        self::assertNull($comment->getSubject());
        self::assertNull($comment->getToAddress());
    }

    public function testProperties(): void
    {
        $comment = new Comment();

        $expected = 'message';
        self::assertNull($comment->getMessage());
        $comment->setMessage($expected);
        self::assertSame($expected, $comment->getMessage());

        $expected = 'subject';
        self::assertNull($comment->getSubject());
        $comment->setSubject($expected);
        self::assertSame($expected, $comment->getSubject());

        $expected = Importance::getDefault();
        self::assertSame($expected, $comment->getImportance());
        $expected = Importance::URGENT;
        $comment->setImportance($expected);
        self::assertSame($expected, $comment->getImportance());

        self::assertSame([], $comment->getAttachments());
        $expected = [new UploadedFile(__FILE__, __NAMESPACE__)];
        $comment->setAttachments($expected);
        self::assertSame($expected, $comment->getAttachments());
    }

    public function testSetFromAddress(): void
    {
        $comment = new Comment();
        $comment->setFromAddress('test@test.com');
        $expected = new Address('test@test.com');
        $actual = $comment->getFromAddress();
        self::assertEqualsCanonicalizing($expected, $actual);

        $comment->setFromAddress(Address::create('test@test.com'));
        $actual = $comment->getFromAddress();
        self::assertEqualsCanonicalizing($expected, $actual);

        $user = $this->createUser();
        $comment->setFromAddress($user);
        $actual = $comment->getFromAddress();
        $expected = $user->getEmailAddress();
        self::assertEqualsCanonicalizing($expected, $actual);
    }

    public function testSetToAddress(): void
    {
        $comment = new Comment();
        $comment->setToAddress('test@test.com');
        $expected = new Address('test@test.com');
        $actual = $comment->getToAddress();
        self::assertEqualsCanonicalizing($expected, $actual);

        $comment->setToAddress(Address::create('test@test.com'));
        $actual = $comment->getToAddress();
        self::assertEqualsCanonicalizing($expected, $actual);

        $user = $this->createUser();
        $comment->setToAddress($user);
        $actual = $comment->getToAddress();
        $expected = $user->getEmailAddress();
        self::assertEqualsCanonicalizing($expected, $actual);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@test.com')
            ->setUsername('username');

        return $user;
    }
}
