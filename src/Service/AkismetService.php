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

use App\Utils\DateUtils;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to check spamming with the Akismet.
 *
 * @see https://akismet.com/
 */
class AkismetService extends AbstractHttpClientService
{
    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 900;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://rest.akismet.com';

    /**
     * The activity key URI.
     */
    private const URI_ACTIVITY = '1.2/key-sites';

    /**
     * The spam check URI.
     */
    private const URI_SPAM = '1.1/comment-check';

    /**
     * The usage key URI.
     */
    private const URI_USAGE = '1.2/usage-limit';

    /**
     * The URI to verify key.
     */
    private const URI_VERIFY = '1.1/verify-key';

    /**
     * The value returned when the comment is not spam.
     */
    private const VALUE_FALSE = 'false';

    /**
     * The value returned when the API key is valid.
     */
    private const VALUE_VALID = 'valid';

    /**
     * @throws \InvalidArgumentException if the API key is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%akismet_key%')]
        string $key,
        CacheInterface $cache,
        LoggerInterface $logger,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct($key, $cache, $logger);
    }

    /**
     * Gets activity for the given year and month.
     *
     * @param ?int $year  the year or <code>null</code> for the current year
     * @param ?int $month the month or <code>null</code> for the current month
     *
     * @throws ExceptionInterface
     */
    public function activity(?int $year = null, ?int $month = null): array|false
    {
        $date = DateUtils::createDateTime('today');
        $year ??= (int) $date->format('Y');
        $month ??= (int) $date->format('m');
        $body = [
            'api_key' => $this->key,
            'month' => \sprintf('%04d-%02d', $year, $month),
        ];
        $response = $this->requestPost(self::URI_ACTIVITY, [self::BODY => $body]);
        if (!$this->checkError($response)) {
            return false;
        }

        try {
            return $response->toArray();
        } catch (ExceptionInterface $e) {
            return $this->setLastError($e->getCode(), $this->trans('unknown'), $e);
        }
    }

    #[\Override]
    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Verify if the given comment is spam.
     * <p>
     * The parameters options can be one of the following:
     * </p>
     * <ul>
     * <li><b>blog</b> (required): The front page or home URL of the instance making the request. For a blog or wiki,
     * this would be the front page. Must be a full URI, including http://.</li>
     * <li><b>user_ip</b> (required): IP address of the comment submitter.</li>
     * <li><b>user_agent</b>: User agent string of the web browser submitting the comment - typically the
     * HTTP_USER_AGENT cgi variable. Not to be confused with the user agent of the Akismet library.</li>
     * <li><b>referrer</b>: The content of the <code>HTTP_REFERER</code> header should be sent here.</li>
     * <li><b>permalink</b>: The full permanent URL of the entry the comment was submitted to.</li>
     * <li><b>comment_type</b>: A string that describes the type of content being sent. Examples:
     *      <ul>
     *          <li><code>comment</code> A blog comment.</li>
     *          <li><code>forum-post</code> A top-level forum post.</li>
     *          <li><code>reply</code> A reply to a top-level forum post.</li>
     *          <li><code>blog-post</code>: A blog post.</li>
     *          <li><code>contact-form</code>: A contact form or feedback form submission.</li>
     *          <li><code>signup</code>: A new user account.</li>
     *          <li><code>message</code>: A message sent between just a few users.</li>
     *      </ul>
     * </li>
     * <li><b>comment_author</b>: Name submitted with the comment.</li>
     * <li><b>comment_author_email</b>: Email address submitted with the comment.</li>
     * <li><b>comment_author_url</b>: URL submitted with comment.</li>
     * <li><b>comment_date_gmt</b>: The UTC timestamp of the creation of the comment, in ISO 8601 format. May be
     * omitted for comment-check requests if the comment is sent to the API at the time it is created.</li>
     * <li><b>comment_post_modified_gmt</b>: The UTC timestamp of the publication time for the post, page or thread
     * on which the comment was posted.</li>
     * <li><b>blog_lang</b>: Indicates the language(s) in use on the blog or site, in ISO 639-1 format, comma-separated.
     * A site with articles in English and French might use "en, fr_ca".</li>
     * <li><b>blog_charset</b>: The character encoding for the form values included in comment_* parameters, such as
     * "UTF-8" or "ISO-8859-1".</li>
     * <li><b>user_role</b>: The user role of the user who submitted the comment. This is an optional parameter.
     * If you set it to "administrator", Akismet will always return false.</li>
     * <li><b>is_test</b>: This is an optional parameter. You can use it when submitting test queries.</li>
     * </ul>.
     *
     * @param string $comment the comment to validate
     * @param array  $options the parameter options to override
     *
     * @return bool <code>true</code> if the given comment is spam; <code>false</code> otherwise
     *
     * @throws ExceptionInterface
     */
    public function isSpam(string $comment, array $options = [], ?Request $request = null): bool
    {
        $request ??= $this->getCurrentRequest();
        if (!$request instanceof Request) {
            return true;
        }

        $body = $this->getVerifyParameters($request, $comment, $options);
        $response = $this->requestPost(self::URI_SPAM, [self::BODY => $body]);
        if (!$this->checkError($response)) {
            return false;
        }

        return match ($response->getContent()) {
            self::VALUE_FALSE => false,
            default => true
        };
    }

    /**
     * Verify if the API key is valid.
     *
     * @return bool <code>true</code>> if valid; <code>false</code> otherwise
     */
    public function isValidKey(?Request $request = null): bool
    {
        return $this->getUrlCacheValue(self::URI_VERIFY, function () use ($request): bool {
            $request ??= $this->getCurrentRequest();
            if (!$request instanceof Request) {
                return false;
            }
            $body = [
                'api_key' => $this->key,
                'blog' => $request->getSchemeAndHttpHost(),
            ];
            $response = $this->requestPost(self::URI_VERIFY, [self::BODY => $body]);
            if (!$this->checkError($response)) {
                return false;
            }

            return self::VALUE_VALID === $response->getContent();
        });
    }

    /**
     * Gets the report track usage for the current month.
     *
     * @throws ExceptionInterface
     */
    public function usage(): array|false
    {
        $body = ['api_key' => $this->key];
        $response = $this->requestPost(self::URI_USAGE, [self::BODY => $body]);
        if (!$this->checkError($response)) {
            return false;
        }

        try {
            return $response->toArray();
        } catch (ExceptionInterface $e) {
            return $this->setLastError($e->getCode(), $this->trans('unknown'), $e);
        }
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
    }

    /**
     * Checks response error.
     *
     * @return bool false if an error is found; true otherwise
     *
     * @throws ExceptionInterface
     */
    private function checkError(ResponseInterface $response): bool
    {
        $code = $response->getStatusCode();
        if (Response::HTTP_OK !== $code) {
            return $this->setLastError($code, $this->trans('unknown'));
        }

        $headers = $response->getHeaders();
        $code = (int) ($headers['x-akismet-alert-code'][0] ?? 0);
        if (0 !== $code) {
            $message = $this->trans((string) $code);
            if ($message === (string) $code) {
                $message = $headers['x-akismet-alert-msg'][0] ?? $this->trans('unknown');
            }

            return $this->setLastError($code, $message);
        }

        return true;
    }

    private function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getVerifyParameters(Request $request, string $content, array $options): array
    {
        /** @phpstan-var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        $headers = $request->headers;

        return \array_filter(\array_merge([
            'api_key' => $this->key,
            'user_ip' => $request->getClientIp(),
            'user_agent' => $headers->get('User-Agent'),
            'referrer' => $headers->get('referer'),
            'permalink' => $request->getUri(),
            'comment_content' => $content,
            'comment_type' => 'contact-form',
            'comment_author' => $user?->getUserIdentifier(),
            'comment_author_email' => $user?->getEmail(),
            'blog' => $request->getSchemeAndHttpHost(),
            'blog_lang' => $request->getLocale(),
            'blog_charset' => 'UTF-8',
            'is_test' => true,
        ], $options));
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id, [], 'akismet');
    }
}
