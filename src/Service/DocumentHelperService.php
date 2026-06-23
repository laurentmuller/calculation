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

namespace App\Service;

use App\Interfaces\DocumentHelperInterface;
use App\Model\CustomerInformation;
use App\Parameter\UserParameters;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Default implementation of the document helper interface.
 */
readonly class DocumentHelperService implements DocumentHelperInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private UserParameters $parameters,
        private Security $security
    ) {
    }

    #[\Override]
    public function getCustomer(): CustomerInformation
    {
        return $this->parameters->getCustomerInformation();
    }

    #[\Override]
    public function getMinMargin(): float
    {
        return $this->parameters->getApplication()
            ->getMinMargin();
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    #[\Override]
    public function getUserIdentifier(): ?string
    {
        return $this->security->getUser()?->getUserIdentifier();
    }
}
