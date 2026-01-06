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
use App\Model\UserComment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;

final class UserCommentTest extends TestCase
{
    public function testConstructor(): void
    {
        $comment = new UserComment();
        self::assertSame([], $comment->getAttachments());
        self::assertNull($comment->getFrom());
        self::assertSame(Importance::getDefault(), $comment->getImportance());
        self::assertNull($comment->getMessage());
        self::assertNull($comment->getSubject());
        self::assertNull($comment->getTo());
    }

    public function testInstance(): void
    {
        $subject = 'subject';
        $user = $this->createUser();
        $comment = UserComment::instance($subject, $user, $user);
        self::assertSame('subject', $comment->getSubject());
        $expected = $user->getAddress();
        self::assertEqualsCanonicalizing($expected, $comment->getFrom());
        self::assertEqualsCanonicalizing($expected, $comment->getTo());
    }

    public function testProperties(): void
    {
        $comment = new UserComment();

        self::assertNull($comment->getMessage());
        $comment->setMessage('message');
        self::assertSame('message', $comment->getMessage());

        self::assertNull($comment->getSubject());
        $comment->setSubject('subject');
        self::assertSame('subject', $comment->getSubject());

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

    public function testSetFrom(): void
    {
        $comment = new UserComment();
        $comment->setFrom('test@test.com');
        $expected = new Address('test@test.com');
        $actual = $comment->getFrom();
        self::assertEqualsCanonicalizing($expected, $actual);

        $comment->setFrom(Address::create('test@test.com'));
        $actual = $comment->getFrom();
        self::assertEqualsCanonicalizing($expected, $actual);

        $user = $this->createUser();
        $comment->setFrom($user);
        $actual = $comment->getFrom();
        $expected = $user->getAddress();
        self::assertEqualsCanonicalizing($expected, $actual);
    }

    public function testSetTo(): void
    {
        $comment = new UserComment();
        $comment->setTo('test@test.com');
        $expected = new Address('test@test.com');
        $actual = $comment->getTo();
        self::assertEqualsCanonicalizing($expected, $actual);

        $comment->setTo(Address::create('test@test.com'));
        $actual = $comment->getTo();
        self::assertEqualsCanonicalizing($expected, $actual);

        $user = $this->createUser();
        $comment->setTo($user);
        $actual = $comment->getTo();
        $expected = $user->getAddress();
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
