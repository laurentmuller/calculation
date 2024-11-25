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

namespace App\Tests\Captcha;

use App\Captcha\LetterCaptcha;
use App\Service\DictionaryService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AlphaCaptchaTestCase<LetterCaptcha>
 */
class LetterCaptchaTest extends AlphaCaptchaTestCase
{
    public function testNegativeIndex(): void
    {
        $captcha = new class($this->service, $this->translator) extends LetterCaptcha {
            protected function getRandomIndex(): int
            {
                return -1;
            }
        };
        $challenge = $captcha->getChallenge();
        $actual = $captcha->checkAnswer($challenge->answer, $challenge->answer);
        self::assertTrue($actual);
    }

    protected function createCaptcha(DictionaryService $service, TranslatorInterface $translator): LetterCaptcha
    {
        return new LetterCaptcha($service, $translator);
    }
}
