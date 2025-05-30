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
use App\Service\DictionaryService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template TCaptcha as AbstractAlphaCaptcha
 */
abstract class AlphaCaptchaTestCase extends TestCase
{
    use TranslatorMockTrait;

    protected MockObject&DictionaryService $service;
    protected MockObject&TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $letters = \implode('', \range('A', 'Z'));
        $this->translator = $this->createMockTranslator();
        $this->service = $this->createMock(DictionaryService::class);
        $this->service->method('getRandomWord')
            ->willReturn($letters);
    }

    public function testCheckAnswer(): void
    {
        $captcha = $this->createCaptcha($this->service, $this->translator);
        $challenge = $captcha->getChallenge();
        $actual = $captcha->checkAnswer($challenge->answer, $challenge->answer);
        self::assertTrue($actual);
    }

    /**
     * @phpstan-return TCaptcha
     */
    abstract protected function createCaptcha(
        DictionaryService $service,
        TranslatorInterface $translator
    ): AbstractAlphaCaptcha;
}
