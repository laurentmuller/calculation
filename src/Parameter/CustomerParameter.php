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

namespace App\Parameter;

use App\Attribute\Parameter;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Customer parameter.
 */
class CustomerParameter implements ParameterInterface
{
    #[Parameter('customer_address')]
    private ?string $address = null;

    #[Assert\Email]
    #[Parameter('customer_email')]
    private ?string $email = null;

    #[Assert\Url(requireTld: true)]
    #[Parameter('customer_facebook')]
    private ?string $facebook = null;

    #[Assert\Url(requireTld: true)]
    #[Parameter('customer_instagram')]
    private ?string $instagram = null;

    #[Assert\Url(requireTld: true)]
    #[Parameter('customer_linkedin')]
    private ?string $linkedin = null;

    #[Assert\NotBlank]
    #[Parameter('customer_name')]
    private ?string $name = null;

    #[Parameter('customer_phone')]
    private ?string $phone = null;

    #[Assert\Url(requireTld: true)]
    #[Parameter('customer_url')]
    private ?string $url = null;

    #[Parameter('customer_zip_city')]
    private ?string $zipCity = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_customer';
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getZipCity(): ?string
    {
        return $this->zipCity;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function setLinkedin(?string $linkedin): self
    {
        $this->linkedin = $linkedin;

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setZipCity(?string $zipCity): self
    {
        $this->zipCity = $zipCity;

        return $this;
    }
}
