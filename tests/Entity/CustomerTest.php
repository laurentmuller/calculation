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
use App\Utils\FormatUtils;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Constraints\IsNullValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<IsNullValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class CustomerTest extends ConstraintValidatorTestCase
{
    use IdTrait;

    public function testAge(): void
    {
        $customer = new Customer();
        self::assertNull($customer->age);

        $currentDate = DateUtils::createDate();
        $birthday = DateUtils::sub($currentDate, 'P1Y');
        $customer->withBirthday(clone $birthday);
        /** @var int|null $actual */
        $actual = $customer->age;
        self::assertIsInt($actual);
        self::assertSame(1, $actual);

        $birthday = DateUtils::add($birthday, 'P1D');
        $customer->withBirthday(clone $birthday);
        /** @var int|null $actual */
        $actual = $customer->age;
        self::assertIsInt($actual);
        self::assertSame(0, $actual);
    }

    public function testConstruct(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $customer = new Customer();
        self::assertNull($customer->id);
        self::assertNull($customer->address);
        self::assertNull($customer->age);
        self::assertNull($customer->birthday);
        self::assertNull($customer->city);
        self::assertNull($customer->company);
        self::assertSame('CH', $customer->country);
        self::assertSame('Suisse', $customer->countryName);
        self::assertNull($customer->email);
        self::assertNull($customer->firstName);
        self::assertNull($customer->lastName);
        self::assertNull($customer->title);
        self::assertNull($customer->webSite);
        self::assertNull($customer->zipCode);
    }

    public function testDisplay(): void
    {
        $customer = new Customer();
        self::assertSame('0', $customer->getDisplay());
        self::setId($customer, 10);
        self::assertSame('10', $customer->getDisplay());

        $customer->withFirstName('John');
        self::assertSame('John', $customer->getDisplay());

        $customer->withCompany('company');
        self::assertSame('John, company', $customer->getDisplay());
    }

    public function testFullName(): void
    {
        $customer = new Customer();
        self::assertNull($customer->fullName);

        $customer = new Customer();
        $customer->withFirstName('John');
        self::assertSame('John', $customer->fullName);

        $customer = new Customer();
        $customer->withLastName('Doe');
        self::assertSame('Doe', $customer->fullName);

        $customer = new Customer();
        $customer->withLastName('Doe');
        $customer->withFirstName('John');
        self::assertSame('Doe John', $customer->fullName);
    }

    public function testId(): void
    {
        $customer = new Customer();
        self::assertNull($customer->id);
        self::setId($customer, 10);
        self::assertSame(10, $customer->id);
    }

    public function testNameOrCompany(): void
    {
        $customer = new Customer();
        self::assertNull($customer->nameOrCompany);
    }

    public function testSetProperties(): void
    {
        $customer = new Customer();
        $customer->withAddress('address');
        self::assertSame('address', $customer->address);
        $customer->withTitle('title');
        self::assertSame('title', $customer->title);
        $customer->withWebSite('website');
        self::assertSame('https://website', $customer->webSite);
        $customer->withWebSite('https://www.example.com');
        self::assertSame('https://www.example.com', $customer->webSite);
        $customer->withZipCode('zipCode');
        self::assertSame('zipCode', $customer->zipCode);
        $customer->withCountry('country');
        self::assertSame('country', $customer->country);
        $customer->withEmail('email@example.com');
        self::assertSame('email@example.com', $customer->email);
    }

    public function testTitleAndFullName(): void
    {
        $customer = new Customer();
        self::assertNull($customer->titleAndFullName);
    }

    public function testValidateNoViolation(): void
    {
        $customer = new Customer();
        $customer->withFirstName('John');
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
        self::assertNull($customer->zipCity);
        $customer->withZipCode('zipCode');
        self::assertSame('zipCode', $customer->zipCity);
        $customer->withCity('city');
        self::assertSame('zipCode city', $customer->zipCity);
    }

    #[\Override]
    protected function createValidator(): IsNullValidator
    {
        return new IsNullValidator();
    }
}
