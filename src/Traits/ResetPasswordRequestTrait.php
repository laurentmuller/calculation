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

namespace App\Traits;

use App\Utils\DateUtils;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

/**
 * @phpstan-require-implements ResetPasswordRequestInterface
 */
trait ResetPasswordRequestTrait
{
    #[ORM\Column(type: DatePointType::NAME, nullable: true)]
    private ?DatePoint $expiresAt = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $hashedToken = null;

    #[ORM\Column(type: DatePointType::NAME, nullable: true)]
    private ?DatePoint $requestedAt = null;

    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $selector = null;

    /**
     * Removes the reset password request values.
     */
    public function eraseResetPasswordRequest(): self
    {
        $this->requestedAt = null;
        $this->expiresAt = null;
        $this->selector = null;
        $this->hashedToken = null;

        return $this;
    }

    #[\Override]
    public function getExpiresAt(): DatePoint
    {
        return $this->expiresAt ?? DateUtils::createDatePoint();
    }

    #[\Override]
    public function getHashedToken(): string
    {
        return (string) $this->hashedToken;
    }

    #[\Override]
    public function getRequestedAt(): DatePoint
    {
        return $this->requestedAt ?? DateUtils::createDatePoint();
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    #[\Override]
    public function getUser(): self
    {
        return $this;
    }

    #[\Override]
    public function isExpired(): bool
    {
        return !$this->expiresAt instanceof DatePoint || $this->expiresAt->getTimestamp() <= \time();
    }

    /**
     * Returns a value indicating if the reset password was requested.
     */
    public function isResetPassword(): bool
    {
        return StringUtils::isString($this->hashedToken);
    }

    /**
     * Sets the reset password request values.
     *
     * @param string $selector    a non-hashed random string used to fetch a request from persistence
     * @param string $hashedToken the hashed token used to verify a reset request
     */
    public function setResetPasswordRequest(DatePoint $expiresAt, string $selector, string $hashedToken): self
    {
        $this->expiresAt = $expiresAt;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->requestedAt = DateUtils::createDatePoint();

        return $this;
    }
}
