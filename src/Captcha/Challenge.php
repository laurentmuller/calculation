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

namespace App\Captcha;

/**
 * The captcha challenge.
 */
readonly class Challenge
{
    /**
     * @param string $question the question
     * @param string $answer   the answer
     */
    public function __construct(public string $question, public string $answer)
    {
    }
}
