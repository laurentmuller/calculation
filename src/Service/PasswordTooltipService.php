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

use App\Enums\StrengthLevel;
use App\Interfaces\PropertyServiceInterface;
use App\Parameter\ApplicationParameters;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to get password tooltips.
 */
readonly class PasswordTooltipService
{
    public function __construct(
        private ApplicationParameters $parameters,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @return string[]
     */
    public function getTooltips(): array
    {
        $results = [];
        $level = $this->parameters->getSecurity()->getLevel();
        if (StrengthLevel::NONE !== $level) {
            $prefix = $this->trans('security_strength_level');
            $suffix = $level->trans($this->translator);
            $results[] = \sprintf('%s : %s', $prefix, $suffix);
        }

        $constraint = $this->parameters->getSecurity()->getPasswordConstraint();
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $property => $option) {
            if ($constraint->isOption($option)) {
                $results[] = $this->trans($property);
            }
        }

        if ($this->parameters->getSecurity()->isCompromised()) {
            $results[] = $this->trans('security_compromised_password');
        }

        return $results;
    }

    private function trans(string $id): string
    {
        return $this->translator->trans('password.' . $id);
    }
}
