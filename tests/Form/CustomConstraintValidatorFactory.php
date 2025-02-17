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

namespace App\Tests\Form;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class CustomConstraintValidatorFactory extends ConstraintValidatorFactory
{
    /**
     * @param array<class-string<Constraint>, ConstraintValidatorInterface> $constraints
     */
    public function __construct(private readonly array $constraints)
    {
        parent::__construct();
    }

    #[\Override]
    public function getInstance(Constraint $constraint): ConstraintValidatorInterface
    {
        return $this->constraints[$constraint::class] ?? parent::getInstance($constraint);
    }
}
