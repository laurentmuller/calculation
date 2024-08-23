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

use App\Captcha\VowelCaptcha;
use App\Service\DictionaryService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AlphaCaptchaTestCase<VowelCaptcha>
 */
class VowelCaptchaTest extends AlphaCaptchaTestCase
{
    protected function createCaptcha(DictionaryService $service, TranslatorInterface $translator): VowelCaptcha
    {
        return new VowelCaptcha($service, $translator);
    }
}
