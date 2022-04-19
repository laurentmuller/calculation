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
 * Class implementing this interface deals with question and answer validation.
 *
 * @author Laurent Muller
 */
interface AlphaCaptchaInterface
{
    /**
     * Checks if the given answer is correct again the expected answer.
     */
    public function checkAnswer(string $givenAnswer, string $expectedAnswer): bool;

    /**
     * Gets the challenge.
     *
     * @return string[] the question and the answer
     */
    public function getChallenge(): array;

    /**
     * Gets the default index name.
     */
    public static function getDefaultIndexName(): string;
}
