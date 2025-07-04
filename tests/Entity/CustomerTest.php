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

namespace App\Tests\Entity;

use App\Entity\Customer;
use App\Utils\DateUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints\IsNullValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<IsNullValidator>
 */
class CustomerTest extends ConstraintValidatorTestCase
{
    use IdTrait;

    /**
     * @throws \Exception
     */
    public function testAge(): void
    {
        $customer = new Customer();
        self::assertNull($customer->getAge());

        $currentDate = new DatePoint();

        $birthday = DateUtils::sub($currentDate, 'P1Y');
        $customer->setBirthday(clone $birthday);

        $age = $customer->getAge();
        self::assertSame(1, $age); // @phpstan-ignore-line

        $birthday = DateUtils::add($birthday, 'P1D');
        $customer->setBirthday(clone $birthday);

        $age = $customer->getAge();
        self::assertSame(0, $age); // @phpstan-ignore-line
    }

    public function testConstruct(): void
    {
        \Locale::setDefault('fr-CH');
        $customer = new Customer();
        self::assertNull($customer->getId());
        self::assertNull($customer->getAddress());
        self::assertNull($customer->getAge());
        self::assertNull($customer->getBirthday());
        self::assertNull($customer->getCity());
        self::assertNull($customer->getCompany());
        self::assertSame('CH', $customer->getCountry());
        self::assertNull($customer->getEmail());
        self::assertNull($customer->getFirstName());
        self::assertNull($customer->getLastName());
        self::assertNull($customer->getTitle());
        self::assertNull($customer->getWebSite());
        self::assertNull($customer->getZipCode());
    }

    /**
     * @throws \ReflectionException
     */
    public function testDisplay(): void
    {
        $customer = new Customer();
        self::assertSame('0', $customer->getDisplay());
        self::setId($customer, 10);
        self::assertSame('10', $customer->getDisplay());

        $customer->setFirstName('John');
        self::assertSame('John', $customer->getDisplay());

        $customer->setCompany('company');
        self::assertSame('John, company', $customer->getDisplay());
    }

    public function testFullName(): void
    {
        $customer = new Customer();
        self::assertSame('', $customer->getFullName());

        $customer = new Customer();
        $customer->setFirstName('John');
        self::assertSame('John', $customer->getFullName());

        $customer = new Customer();
        $customer->setLastName('Doe');
        self::assertSame('Doe', $customer->getFullName());

        $customer = new Customer();
        $customer->setLastName('Doe');
        $customer->setFirstName('John');
        self::assertSame('Doe John', $customer->getFullName());
    }

    /**
     * @throws \ReflectionException
     */
    public function testId(): void
    {
        $customer = new Customer();
        self::assertNull($customer->getId());
        self::setId($customer, 10);
        self::assertSame(10, $customer->getId());
    }

    public function testNameOrCompany(): void
    {
        $customer = new Customer();
        self::assertNull($customer->getNameOrCompany());
    }

    public function testSetProperties(): void
    {
        $customer = new Customer();
        $customer->setAddress('address');
        self::assertSame('address', $customer->getAddress());
        $customer->setTitle('title');
        self::assertSame('title', $customer->getTitle());
        $customer->setWebSite('website');
        self::assertSame('https://website', $customer->getWebSite());
        $customer->setWebSite('https://www.example.com');
        self::assertSame('https://www.example.com', $customer->getWebSite());
        $customer->setZipCode('zipCode');
        self::assertSame('zipCode', $customer->getZipCode());
        $customer->setCountry('country');
        self::assertSame('country', $customer->getCountry());
        $customer->setEmail('email@example.com');
        self::assertSame('email@example.com', $customer->getEmail());
    }

    public function testTitleAndFullName(): void
    {
        $customer = new Customer();
        self::assertSame('', $customer->getTitleAndFullName());
    }

    public function testValidateNoViolation(): void
    {
        $customer = new Customer();
        $customer->setFirstName('John');
        $customer->validate($this->context);
        $this->assertNoViolation();
    }

    public function testValidateRaised(): void
    {
        $customer = new Customer();
        $customer->validate($this->context);
        $this->buildViolation('customer.empty')
            ->assertRaised();
    }

    public function testZipCity(): void
    {
        $customer = new Customer();
        self::assertSame('', $customer->getZipCity());
        $customer->setZipCode('zipCode');
        self::assertSame('zipCode', $customer->getZipCity());
        $customer->setCity('city');
        self::assertSame('zipCode city', $customer->getZipCity());
    }

    #[\Override]
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new IsNullValidator();
    }
}
