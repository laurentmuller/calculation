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
use App\Captcha\VowelCaptcha;
use App\Service\DictionaryService;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends AlphaCaptchaTestCase<VowelCaptcha>
 */
#[CoversClass(VowelCaptcha::class)]
#[CoversClass(AbstractAlphaCaptcha::class)]
class VowelCaptchaTest extends AlphaCaptchaTestCase
{
    protected function createCaptcha(DictionaryService $service): VowelCaptcha
    {
        return new VowelCaptcha($service);
    }
}
