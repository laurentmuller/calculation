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

use App\Captcha\AbstractAlphaCaptcha;
use App\Captcha\ConsonantCaptcha;
use App\Service\DictionaryService;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends AlphaCaptchaTestCase<ConsonantCaptcha>
 */
#[CoversClass(ConsonantCaptcha::class)]
#[CoversClass(AbstractAlphaCaptcha::class)]
class ConsonantCaptchaTest extends AlphaCaptchaTestCase
{
    protected function createCaptcha(DictionaryService $service): ConsonantCaptcha
    {
        return new ConsonantCaptcha($service);
    }
}
