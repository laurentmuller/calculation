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

use App\Constraint\Password;
use App\Enums\StrengthLevel;
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
        $security = $this->parameters->getSecurity();
        $level = $security->getLevel();
        if (StrengthLevel::NONE !== $level) {
            $prefix = $this->trans('strengthLevel');
            $suffix = $level->trans($this->translator);
            $results[] = \sprintf('%s : %s', $prefix, $suffix);
        }

        $constraint = $security->getPasswordConstraint();
        foreach (Password::ALLOWED_OPTIONS as $option) {
            if ($constraint->isOption($option)) {
                $results[] = $this->trans($option);
            }
        }

        if ($security->isCompromised()) {
            $results[] = $this->trans('compromisedPassword');
        }

        return $results;
    }

    private function trans(string $id): string
    {
        return $this->translator->trans('password.' . $id);
    }
}
