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

namespace App\Validator;

use App\Enums\StrengthLevel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Strength constraint.
 *
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Strength extends Constraint
{
    final public const IS_STRENGTH_ERROR = '6218da5e-12d8-481e-b0fc-9bc4cbaad2ef';

    /**
     * The password strength level.
     */
    public StrengthLevel $minimum;

    /**
     * The password strength error message.
     */
    public string $strength_message = 'password.strength_level';

    /**
     * Constructor.
     *
     * @throws InvalidArgumentException if the minimum parameter is an integer and cannot be parsed to a strength level
     */
    public function __construct(StrengthLevel|int $minimum = StrengthLevel::NONE, public ?string $userNamePath = null, public ?string $emailPath = null)
    {
        parent::__construct();
        if (\is_int($minimum)) {
            $level = StrengthLevel::tryFrom($minimum);
            if (!$level instanceof StrengthLevel) {
                throw new InvalidArgumentException(\sprintf('Unable to find a strength level for the value %d.', $minimum));
            }
            $this->minimum = $level;
        } else {
            $this->minimum = $minimum;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'minimum';
    }
}
