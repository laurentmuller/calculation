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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents a customer.
 */
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'sy_Customer')]
#[ORM\UniqueConstraint(name: 'unique_customer_email', columns: ['email'])]
#[UniqueEntity(fields: 'email', message: 'customer.unique_email')]
class Customer extends AbstractEntity
{
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $address = null;

    #[Assert\Date]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $birthday = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $city = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $company = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $country = null;

    #[Assert\Email]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $email = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(name: 'firstName', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $firstName = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(name: 'lastName', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $lastName = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    protected ?string $title = null;

    #[Assert\Url]
    #[Assert\Length(max: 100)]
    #[ORM\Column(name: 'webSite', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $webSite = null;

    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^[1-9]\d{3}$/', message: 'customer.zip_code')]
    #[ORM\Column(name: 'zipCode', type: Types::STRING, length: 10, nullable: true)]
    protected ?string $zipCode = null;

    /**
     * Get address.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Get birthday.
     */
    public function getBirthday(): ?\DateTimeInterface
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
     * {@inheritdoc}
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
     * @see \App\Entity\Customer::getFullName()
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
        return $this->getFullName() ?: $this->getCompany();
    }

    /**
     * Get title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
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
     * Set address.
     */
    public function setAddress(?string $address): self
    {
        $this->address = $this->trim($address);

        return $this;
    }

    /**
     * Set birthday.
     */
    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Set city.
     */
    public function setCity(?string $city): self
    {
        $this->city = $this->trim($city);

        return $this;
    }

    /**
     * Set company.
     */
    public function setCompany(?string $company): self
    {
        $this->company = $this->trim($company);

        return $this;
    }

    /**
     * Set country.
     */
    public function setCountry(?string $country): self
    {
        $this->country = $this->trim($country);

        return $this;
    }

    /**
     * Set email.
     */
    public function setEmail(?string $email): self
    {
        $this->email = $this->trim($email);

        return $this;
    }

    /**
     * Set first name.
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $this->trim($firstName);

        return $this;
    }

    /**
     * Set last name.
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $this->trim($lastName);

        return $this;
    }

    /**
     * Set title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $this->trim($title);

        return $this;
    }

    /**
     * Set web site.
     */
    public function setWebSite(?string $webSite): self
    {
        $webSite = $this->trim($webSite);
        if ($webSite && !\str_starts_with($webSite, 'http')) {
            $webSite = "https://$webSite";
        }
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * Set zip code.
     */
    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $this->trim($zipCode);

        return $this;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // check values
        if (empty($this->firstName) && empty($this->lastName) && empty($this->company)) {
            $context->buildViolation('customer.empty')
                ->addViolation();
        }
    }

    /**
     * Join 2 elements with a string.
     * If both elements are empty, an empty string is returned;
     * if one of elements is empty, the other element is returned
     * else both elements are returned with the separator.
     *
     * @param string|null $str1 the first element
     * @param string|null $str2 the second element
     * @param string      $sep  the separator
     *
     * @return string the joined elements
     */
    private function concat(?string $str1, ?string $str2, string $sep = ' '): string
    {
        return \implode($sep, \array_filter([$str1, $str2]));
    }
}
