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

namespace App\Interfaces;

use App\Model\CustomerInformation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class implementing this interface provides methods to help generate documents.
 */
interface DocumentHelperInterface
{
    /**
     * Gets the customer information.
     */
    public function getCustomer(): CustomerInformation;

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float;

    /**
     * Gets the translator.
     */
    public function getTranslator(): TranslatorInterface;

    /**
     * Gets the connected user identifier or null if not connected.
     */
    public function getUserIdentifier(): ?string;
}
