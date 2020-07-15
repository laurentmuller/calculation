<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a customer.
 *
 * @ORM\Table(name="sy_Customer")
 * @ORM\Entity(repositoryClass="App\Repository\CustomerRepository")
 * @UniqueEntity(fields="email", message="customer.unique_email")
 */
class Customer extends AbstractEntity
{
    /**
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $address;

    /**
     * @ORM\Column(name="birthday", type="date", nullable=true)
     * @Assert\Date
     *
     * @var \DateTime
     */
    protected $birthday;

    /**
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $city;

    /**
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $company;

    /**
     * @ORM\Column(name="country", type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     *
     * @var string
     */
    protected $country;

    /**
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     * @Assert\Email
     * @Assert\Length(max=100)
     *
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(name="firstName", type="string", length=255, nullable=true)
     *
     * @var string
     */
    protected $firstName;

    /**
     * @ORM\Column(name="lastName", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $lastName;

    /**
     * @ORM\Column(name="title", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     *
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(name="webSite", type="string", length=100, nullable=true)
     * @Assert\Url
     * @Assert\Length(max=100)
     *
     * @var string
     */
    protected $webSite;

    /**
     * @ORM\Column(name="zipCode", type="string", length=10, nullable=true)
     * @Assert\Regex(pattern="/^[1-9]\d{3}$/", message="customer.zip_code")
     * @Assert\Length(max=10)
     *
     * @var string
     */
    protected $zipCode;

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get company.
     *
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Entity\AbstractEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        if ($this->firstName || $this->lastName || $this->company) {
            return $this->getNameAndCompany();
        }

        return parent::getDisplay();
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Gets the last name and the first name separate by a space character.
     *
     * @return string the last name and the first name,
     */
    public function getFullName(): string
    {
        return $this->concat($this->lastName, $this->firstName);
    }

    /**
     * Get lastName.
     *
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Gets the full name and the company separate by a comma character.
     *
     * @return string the full name and the company
     *
     * @see \App\Entity\Customer::getFullName()
     */
    public function getNameAndCompany(): string
    {
        return $this->concat($this->getFullName(), $this->company, ', ');
    }

    /**
     * Gets the full name or if null the company.
     *
     * @return string
     */
    public function getNameOrCompany(): ?string
    {
        return $this->getFullName() ?: $this->getCompany();
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Get web site.
     *
     * @return string
     */
    public function getWebSite(): ?string
    {
        return $this->webSite;
    }

    /**
     * Gets the zip code and the city separate by a space character.
     *
     * @return string the zip code and the city
     */
    public function getZipCity(): string
    {
        return $this->concat($this->zipCode, $this->city);
    }

    /**
     * Get zip code.
     *
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * Set address.
     *
     * @param string $address
     */
    public function setAddress(?string $address): self
    {
        $this->address = $this->trim($address);

        return $this;
    }

    /**
     * Set birthday.
     *
     * @param \DateTime $birthday
     */
    public function setBirthday(?\DateTime $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Set city.
     *
     * @param string $city
     */
    public function setCity(?string $city): self
    {
        $this->city = $this->trim($city);

        return $this;
    }

    /**
     * Set company.
     *
     * @param string $company
     */
    public function setCompany(?string $company): self
    {
        $this->company = $this->trim($company);

        return $this;
    }

    /**
     * Set country.
     *
     * @param string $country
     */
    public function setCountry(?string $country): self
    {
        $this->country = $this->trim($country);

        return $this;
    }

    /**
     * Set email.
     *
     * @param string $email
     */
    public function setEmail(?string $email): self
    {
        $this->email = $this->trim($email);

        return $this;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $this->trim($firstName);

        return $this;
    }

    /**
     * Set lastName.
     *
     * @param string $lastName
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $this->trim($lastName);

        return $this;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle(?string $title): self
    {
        $this->title = $this->trim($title);

        return $this;
    }

    /**
     * Set web site.
     *
     * @param string $webSite
     */
    public function setWebSite(?string $webSite): self
    {
        $webSite = $this->trim($webSite);
        if ($webSite && 'http' !== \substr($webSite, 0, 4)) {
            $webSite = 'http://' . $webSite;
        }
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * Set zip code.
     *
     * @param string $zipCode
     */
    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $this->trim($zipCode);

        return $this;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context the execution context
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // check values
        if (empty($this->firstName) && empty($this->lastName) && empty($this->company)) {
            $context->buildViolation('customer.empty')
                ->addViolation();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->firstName,
            $this->lastName,
            $this->company,
            $this->address,
            $this->zipCode,
            $this->city,
            $this->email,
        ];
    }

    /**
     * Join 2 elements with a string.
     * If both elements are empty, an empty string is returned;
     * if one of elements is empty, the other element is returned
     * else both elements are returned with the separator.
     *
     * @param string $str1 the first element
     * @param string $str2 the second element
     * @param string $sep  the separator
     *
     * @return string the joined elements
     */
    private function concat(?string $str1, ?string $str2, $sep = ' '): string
    {
        return \implode($sep, \array_filter([$str1, $str2]));
    }
}
