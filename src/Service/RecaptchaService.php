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

    private string $action = 'login';
    private float $scoreThreshold = 0.5;
    private int $timeoutSeconds = 60;

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%google_recaptcha_site_key%')]
        private readonly string $siteKey,
        #[\SensitiveParameter]
        #[Autowire('%google_recaptcha_secret_key%')]
        private readonly string $secretKey,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function setScoreThreshold(float $scoreThreshold): self
    {
        $this->scoreThreshold = $this->validateFloatRange($scoreThreshold, 0, 1);

        return $this;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): self
    {
        $this->timeoutSeconds = \max(0, $timeoutSeconds);

        return $this;
    }

    /**
     * @return string[]
     */
    public function translateErrors(array $codes): array
    {
        $errors = \array_map(fn (mixed $code): string => $this->trans("recaptcha.$code", [], 'validators'), $codes);
        if (empty($errors)) {
            $errors[] = $this->trans('recaptcha.unknown-error', [], 'validators');
        }

        return $errors;
    }

    public function verify(Request $request, string $response): Response
    {
        $hostname = (string) $request->server->get('SERVER_NAME');
        $remoteIp = (string) $request->server->get('REMOTE_ADDR');
        $expectedHostName = $this->debug ? $remoteIp : $hostname;

        $recaptcha = new ReCaptcha($this->secretKey);
        $recaptcha->setExpectedHostname($expectedHostName)
            ->setChallengeTimeout($this->timeoutSeconds)
            ->setScoreThreshold($this->scoreThreshold)
            ->setExpectedAction($this->action);

        return $recaptcha->verify($response, $remoteIp);
    }
}
