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

use App\Traits\MathTrait;
use App\Traits\TranslatorTrait;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to validate a reCaptcha.
 */
class RecaptchaService
{
    use MathTrait;
    use TranslatorTrait;
    private int $challengeTimeout = 60;

    private string $expectedAction = 'login';
    private float $scoreThreshold = 0.5;

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%google_recaptcha_site_key%')]
        private readonly string $siteKey,
        #[\SensitiveParameter]
        #[Autowire('%google_recaptcha_secret_key%')]
        private readonly string $secretKey,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getExpectedAction(): string
    {
        return $this->expectedAction;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @psalm-api
     */
    public function setChallengeTimeout(int $challengeTimeout): self
    {
        $this->challengeTimeout = \max(0, $challengeTimeout);

        return $this;
    }

    /**
     * @psalm-api
     */
    public function setExpectedAction(string $expectedAction): self
    {
        $this->expectedAction = $expectedAction;

        return $this;
    }

    /**
     * @psalm-api
     */
    public function setScoreThreshold(float $scoreThreshold): self
    {
        $this->scoreThreshold = $this->validateRange($scoreThreshold, 0, 1);

        return $this;
    }

    /**
     * @return string[]
     */
    public function translateErrors(array $codes): array
    {
        if ([] === $codes) {
            return [$this->translateError('recaptcha.unknown-error')];
        }

        return \array_map(fn (mixed $code): string => $this->translateError("recaptcha.$code"), $codes);
    }

    public function verify(string $response, ?Request $request = null): Response
    {
        $request ??= Request::createFromGlobals();
        $recaptcha = new ReCaptcha($this->secretKey);
        $recaptcha->setChallengeTimeout($this->challengeTimeout)
            ->setScoreThreshold($this->scoreThreshold)
            ->setExpectedAction($this->expectedAction)
            ->setExpectedHostname($request->getHost());

        return $recaptcha->verify($response, $request->getClientIp());
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'validators');
    }
}
