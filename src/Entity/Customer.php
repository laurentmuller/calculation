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

namespace App\Entity;

use App\Repository\CustomerRepository;
use App\Utils\DateUtils;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\DayPointType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a customer.
 */
#[ORM\Table(name: 'sy_Customer')]
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_customer_email', columns: ['email'])]
#[UniqueEntity(fields: 'email', message: 'customer.unique_email')]
class Customer extends AbstractEntity
{
    /** The address. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $address = null {
        set => StringUtils::trim($value);
    }

    /** Get contact age or null if the birthday is null. */
    public ?int $age {
        get {
            if (!$this->birthday instanceof DatePoint) {
                return null;
            }

            $today = DateUtils::createDate();
            $age = DateUtils::getYear($today) - DateUtils::getYear($this->birthday);
            $previous = DateUtils::sub($today, \sprintf('P%dY', $age));

            return $this->birthday > $previous ? --$age : $age;
        }
    }

    /** The contact birthday. */
    #[ORM\Column(type: DayPointType::NAME, nullable: true)]
    public ?DatePoint $birthday = null {
        set(?DatePoint $birthday) {
            if ($birthday instanceof DatePoint) {
                $birthday = DateUtils::removeTime($birthday);
            }
            $this->birthday = $birthday;
        }
    }

    /** The city. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $city = null {
        set => StringUtils::trim($value);
    }

    /** The company. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $company = null {
        set => StringUtils::trim($value);
    }

    /** The country. */
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    public ?string $country = 'CH' {
        set => StringUtils::trim($value);
    }

    /** Get the country name. */
    public string $countryName {
        get => Countries::getNames()[$this->country ?? 'CH'];
    }

    /** The contact or company email. */
    #[Assert\Email]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    public ?string $email = null {
        set => StringUtils::trim($value);
    }

    /** The contact first name. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $firstName = null {
        set => StringUtils::trim($value);
    }

    /** Gets the last name and the first name separate by a space character. */
    public ?string $fullName {
        get => $this->concat($this->lastName, $this->firstName);
    }

    /** The contact last name. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $lastName = null {
        set => StringUtils::trim($value);
    }

    /** Gets the full name and the company separate by a comma character. */
    public ?string $nameAndCompany {
        get => $this->concat($this->fullName, $this->company, ', ');
    }

    /** Gets the full name, if applicable; the company otherwise. */
    public ?string $nameOrCompany {
        get => $this->fullName ?? $this->company;
    }

    /** The contact title. */
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    public ?string $title = null {
        set => StringUtils::trim($value);
    }

    /** Gets the title, the last name, and the first name separate by a space character. */
    public ?string $titleAndFullName {
        get => $this->concat($this->title, $this->fullName);
    }

    /** The website. */
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    public ?string $webSite = null {
        set => $this->trimWebSite($value);
    }

    /** Gets the zip code and the city separate by a space character. */
    public ?string $zipCity {
        get => $this->concat($this->zipCode, $this->city);
    }

    /** The zip code. */
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^[1-9]\d{3}$/', message: 'customer.zip_code')]
    #[ORM\Column(length: 10, nullable: true)]
    public ?string $zipCode = null {
        set => StringUtils::trim($value);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->nameAndCompany ?? parent::getDisplay();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // check values
        if (!StringUtils::isString($this->firstName)
            && !StringUtils::isString($this->lastName)
            && !StringUtils::isString($this->company)) {
            $context->buildViolation('customer.empty')
                ->addViolation();
        }
    }

    /**
     * Set the address.
     */
    public function withAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Set birthday.
     */
    public function withBirthday(?DatePoint $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Set city.
     */
    public function withCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Set company.
     */
    public function withCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Set country.
     */
    public function withCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Set email.
     */
    public function withEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set the first name.
     */
    public function withFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Set the last name.
     */
    public function withLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Set title.
     */
    public function withTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the website.
     */
    public function withWebSite(?string $webSite): self
    {
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * Set zip code.
     */
    public function withZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Join 2 elements with a string.
     *
     * If both elements are null or empty, null is returned
     *
     * @param ?string $str1 the first element
     * @param ?string $str2 the second element
     * @param string  $sep  the separator
     *
     * @return ?string the joined elements if both are null or empty
     */
    private function concat(?string $str1, ?string $str2, string $sep = ' '): ?string
    {
        if (StringUtils::isString($str1) || StringUtils::isString($str2)) {
            return \implode($sep, \array_filter([$str1, $str2]));
        }

        return null;
    }

    private function trimWebSite(?string $value): ?string
    {
        $value = StringUtils::trim($value);
        if (StringUtils::isString($value) && !StringUtils::startWith($value, 'http')) {
            $value = 'https://' . $value;
        }

        return $value;
    }
}
