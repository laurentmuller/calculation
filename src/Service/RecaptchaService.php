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

use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service to validate a reCaptcha.
 */
class RecaptchaService
{
    private string $action = 'login';
    private float $scoreThreshold = 0.5;
    private int $timeoutSeconds = 60;

    public function __construct(
        #[Autowire('%google_recaptcha_site_key%')]
        private readonly string $siteKey,
        #[Autowire('%google_recaptcha_secret_key%')]
        private readonly string $secretKey,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug
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

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function setScoreThreshold(float $scoreThreshold): self
    {
        $this->scoreThreshold = $scoreThreshold;

        return $this;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): self
    {
        $this->timeoutSeconds = $timeoutSeconds;

        return $this;
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
