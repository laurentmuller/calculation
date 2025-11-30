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

    public const ERROR_PREFIX = 'recaptcha.';

    private int $challengeTimeout = 60;
    private string $expectedAction = 'login';
    private ?Response $lastResponse = null;
    private float $scoreThreshold = 0.5;

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%google_recaptcha_site_key%')]
        private readonly string $siteKey,
        private readonly ReCaptcha $reCaptcha,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Gets the timeout, in seconds, to test against the challenge timestamp in <code>verify()</code>.
     */
    public function getChallengeTimeout(): int
    {
        return $this->challengeTimeout;
    }

    /**
     * Gets the action to match against in <code>verify()</code>.
     */
    public function getExpectedAction(): string
    {
        return $this->expectedAction;
    }

    /**
     * Gets the response of the last <code>verify()</code> call.
     */
    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    /**
     * Gets the threshold to meet or exceed in <code>verify()</code>.
     */
    public function getScoreThreshold(): float
    {
        return $this->scoreThreshold;
    }

    /**
     * Gets the site key (public key).
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * Sets a timeout, in seconds, to test against the challenge timestamp in <code>verify()</code>.
     */
    public function setChallengeTimeout(int $challengeTimeout): self
    {
        $this->challengeTimeout = \max(0, $challengeTimeout);

        return $this;
    }

    /**
     * Sets an action to match against in <code>verify()</code>.
     *
     * This should be set per page.
     */
    public function setExpectedAction(string $expectedAction): self
    {
        $this->expectedAction = $expectedAction;

        return $this;
    }

    /**
     * Sets a threshold to meet or exceed in verify().
     *
     * Threshold should be a float between 0 and 1, which will be tested as <code>response >= threshold</code>.
     */
    public function setScoreThreshold(float $scoreThreshold): self
    {
        $this->scoreThreshold = $this->validateRange($scoreThreshold, 0, 1);

        return $this;
    }

    /**
     * Translate the given identifier error.
     */
    public function translateError(string $id): string
    {
        return $this->translator->trans(id: self::ERROR_PREFIX . $id, domain: 'validators');
    }

    /**
     * Translate the given response or errors.
     *
     * @param Response|string[] $codes the response or errors to translate
     *
     * @return string[] the translated errors
     */
    public function translateErrors(Response|array $codes): array
    {
        if ($codes instanceof Response) {
            $codes = $codes->getErrorCodes();
        }
        if ([] === $codes) {
            return [$this->translateError('unknown-error')];
        }

        return \array_map($this->translateError(...), $codes);
    }

    /**
     * Calls the reCAPTCHA site verify API to verify whether the user passes CAPTCHA test and additionally runs any
     * specified additional checks.
     *
     * @param string  $response the user response token provided by reCAPTCHA, verifying the user on your site
     * @param Request $request  the request used to get the host name and the client IP address
     *
     * @return Response the response from the service
     */
    public function verify(string $response, Request $request): Response
    {
        $this->lastResponse = null;
        $this->reCaptcha->setChallengeTimeout($this->challengeTimeout)
            ->setScoreThreshold($this->scoreThreshold)
            ->setExpectedAction($this->expectedAction)
            ->setExpectedHostname($request->getHost());

        return $this->lastResponse = $this->reCaptcha->verify($response, $request->getClientIp());
    }
}
