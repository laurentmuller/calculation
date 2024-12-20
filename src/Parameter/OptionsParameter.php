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

/**
 * Options parameter.
 */
class OptionsParameter implements ParameterInterface
{
    #[Parameter('print_address', false)]
    private bool $printAddress = false;

    #[Parameter('qr_code', false)]
    private bool $qrCode = false;

    public static function getCacheKey(): string
    {
        return 'parameter_option';
    }

    public function isPrintAddress(): bool
    {
        return $this->printAddress;
    }

    public function isQrCode(): bool
    {
        return $this->qrCode;
    }

    public function setPrintAddress(bool $printAddress): self
    {
        $this->printAddress = $printAddress;

        return $this;
    }

    public function setQrCode(bool $qrCode): self
    {
        $this->qrCode = $qrCode;

        return $this;
    }
}
