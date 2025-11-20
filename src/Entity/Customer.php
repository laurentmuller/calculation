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
use Symfony\Bridge\Doctrine\Types\DatePointType;
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
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: DatePointType::NAME, nullable: true)]
    private ?DatePoint $birthday = null;

    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $city = null;

    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $company = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = 'CH';

    #[Assert\Email]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $firstName = null;

    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $lastName = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $title = null;

    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $webSite = null;

    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^[1-9]\d{3}$/', message: 'customer.zip_code')]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    /**
     * Get address.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Get age or null if the birthday is null.
     */
    public function getAge(): ?int
    {
        if (!$this->birthday instanceof DatePoint) {
            return null;
        }

        $today = DateUtils::createDate();
        $age = DateUtils::getYear($today) - DateUtils::getYear($this->birthday);
        $previous = DateUtils::sub($today, \sprintf('P%dY', $age));

        return $this->birthday > $previous ? --$age : $age;
    }

    /**
     * Get birthday.
     */
    public function getBirthday(): ?DatePoint
    {
        return $this->birthday;
    }

    /**
     * Get city.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get company.
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Get country.
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Get the country name.
     */
    public function getCountryName(): ?string
    {
        return Countries::getNames()[$this->country ?? 'CH'];
    }

    #[\Override]
    public function getDisplay(): string
    {
        if (StringUtils::isString($this->firstName) || StringUtils::isString($this->lastName) || StringUtils::isString($this->company)) {
            return $this->getNameAndCompany();
        }

        return parent::getDisplay();
    }

    /**
     * Get email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get firstName.
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Gets the last name and the first name separate by a space character.
     */
    public function getFullName(): string
    {
        return $this->concat($this->lastName, $this->firstName);
    }

    /**
     * Get lastName.
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Gets the full name and the company separate by a comma character.
     *
     * @see Customer::getFullName()
     */
    public function getNameAndCompany(): string
    {
        return $this->concat($this->getFullName(), $this->company, ', ');
    }

    /**
     * Gets the full name, if applicable; the company otherwise.
     */
    public function getNameOrCompany(): ?string
    {
        $fullName = $this->getFullName();

        return '' === $fullName ? $this->getCompany() : $fullName;
    }

    /**
     * Get title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the title, the last name and the first name separate by a space character.
     */
    public function getTitleAndFullName(): string
    {
        return $this->concat($this->title, $this->getFullName());
    }

    /**
     * Get web site.
     */
    public function getWebSite(): ?string
    {
        return $this->webSite;
    }

    /**
     * Gets the zip code and the city separate by a space character.
     */
    public function getZipCity(): string
    {
        return $this->concat($this->zipCode, $this->city);
    }

    /**
     * Get zip code.
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * Set the address.
     */
    public function setAddress(?string $address): self
    {
        $this->address = StringUtils::trim($address);

        return $this;
    }

    /**
     * Set birthday.
     */
    public function setBirthday(?DatePoint $birthday): self
    {
        if ($birthday instanceof DatePoint) {
            $birthday = DateUtils::removeTime($birthday);
        }
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Set city.
     */
    public function setCity(?string $city): self
    {
        $this->city = StringUtils::trim($city);

        return $this;
    }

    /**
     * Set company.
     */
    public function setCompany(?string $company): self
    {
        $this->company = StringUtils::trim($company);

        return $this;
    }

    /**
     * Set country.
     */
    public function setCountry(?string $country): self
    {
        $this->country = StringUtils::trim($country);

        return $this;
    }

    /**
     * Set email.
     */
    public function setEmail(?string $email): self
    {
        $this->email = StringUtils::trim($email);

        return $this;
    }

    /**
     * Set the first name.
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = StringUtils::trim($firstName);

        return $this;
    }

    /**
     * Set the last name.
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = StringUtils::trim($lastName);

        return $this;
    }

    /**
     * Set title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = StringUtils::trim($title);

        return $this;
    }

    /**
     * Set web site.
     */
    public function setWebSite(?string $webSite): self
    {
        $webSite = StringUtils::trim($webSite);
        if (StringUtils::isString($webSite) && !\str_starts_with($webSite, 'http')) {
            $webSite = 'https://' . $webSite;
        }
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * Set zip code.
     */
    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = StringUtils::trim($zipCode);

        return $this;
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
     * Join 2 elements with a string.
     * If both elements are empty, an empty string is returned;
     * if one of the elements is empty, the other element is returned;
     * else both elements are returned with the separator.
     *
     * @param ?string $str1 the first element
     * @param ?string $str2 the second element
     * @param string  $sep  the separator
     *
     * @return string the joined elements
     */
    private function concat(?string $str1, ?string $str2, string $sep = ' '): string
    {
        return \implode($sep, \array_filter([$str1, $str2]));
    }
}
