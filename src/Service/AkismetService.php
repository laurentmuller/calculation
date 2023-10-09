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

use App\Traits\TranslatorAwareTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to check spams with the Akismet.
 *
 * @see https://akismet.com/
 */
class AkismetService extends AbstractHttpClientService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://%s.rest.akismet.com/1.1/';

    /**
     * The comment check URI.
     */
    private const URI_COMMENT_CHECK = 'comment-check';

    /**
     * The verify key URI.
     */
    private const URI_VERIFY = 'verify-key';

    /**
     * The value returned when the comment is not a spam.
     */
    private const VALUE_FALSE = 'false';

    /**
     * The value returned when the API key is valid.
     */
    private const VALUE_VALID = 'valid';

    private readonly string $endpoint;

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key  is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%akismet_key%')]
        string $key,
        private readonly RequestStack $stack,
        private readonly Security $security
    ) {
        parent::__construct($key);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
    }

    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Verify if the given comment is a spam.
     * <p>
     * The parameters options can be one of the following:
     * </p>
     * <ul>
     * <li><b>blog</b> (required): The front page or home URL of the instance making the request. For a blog or wiki this would be the front page. Note: Must be a full URI, including http://.</li>
     * <li><b>user_ip</b> (required): IP address of the comment submitter.</li>
     * <li><b>user_agent</b>: User agent string of the web browser submitting the comment - typically the HTTP_USER_AGENT cgi variable. Not to be confused with the user agent of the Akismet library.</li>
     * <li><b>referrer</b>: The content of the HTTP_REFERER header should be sent here.</li>
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
     * <li><b>comment_date_gmt</b>: The UTC timestamp of the creation of the comment, in ISO 8601 format. May be omitted for comment-check requests if the comment is sent to the API at the time it is created.</li>
     * <li><b>comment_post_modified_gmt</b>: The UTC timestamp of the publication time for the post, page or thread on which the comment was posted.</li>
     * <li><b>blog_lang</b>: Indicates the language(s) in use on the blog or site, in ISO 639-1 format, comma-separated. A site with articles in English and French might use “en, fr_ca”.</li>
     * <li><b>blog_charset</b>: The character encoding for the form values included in comment_* parameters, such as “UTF-8” or “ISO-8859-1”.</li>
     * <li><b>user_role</b>: The user role of the user who submitted the comment. This is an optional parameter. If you set it to “administrator”, Akismet will always return false.</li>
     * <li><b>is_test</b>: This is an optional parameter. You can use it when submitting test queries.</li>
     * </ul>.
     *
     * @param string $content the content to validate
     * @param array  $options the parameter options to override
     *
     * @return bool true if the comment is a spam; false otherwise
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    public function verifyComment(string $content, array $options = []): bool
    {
        $request = $this->getCurrentRequest();
        if (!$request instanceof Request) {
            return true;
        }

        $body = $this->getVerifyParameters($request, $content, $options);
        $response = $this->requestPost(self::URI_COMMENT_CHECK, [
            self::BODY => $body,
        ]);
        if (Response::HTTP_OK !== $response->getStatusCode() || !$this->checkError($response)) {
            return true;
        }

        return match ($response->getContent()) {
            self::VALUE_FALSE => false,
            default => true
        };
    }

    /**
     * Verify that API key is valid.
     *
     * @return bool true if valid; false otherwise
     */
    public function verifyKey(): bool
    {
        return (bool) ($this->getUrlCacheValue(self::URI_VERIFY, fn () => $this->doVerifyKey()) ?? false);
    }

    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => $this->endpoint];
    }

    /**
     * Checks response error.
     *
     * @return bool false if an error is found; true otherwise
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function checkError(ResponseInterface $response): bool
    {
        $headers = $response->getHeaders();
        $code = (int) ($headers['X-akismet-alert-code'][0] ?? 0);
        if (0 !== $code) {
            $message = $this->trans((string) $code, [], 'akismet');
            if ($message === (string) $code) {
                $message = $headers['X-akismet-alert-msg'][0] ?? $this->trans('unknown', [], 'akismet');
            }

            return $this->setLastError($code, $message);
        }

        return true;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doVerifyKey(): ?bool
    {
        $request = $this->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }
        $body = [
            'key' => $this->key,
            'blog' => $request->getSchemeAndHttpHost(),
        ];
        $response = $this->requestPost(self::URI_VERIFY, [
            self::BODY => $body,
        ]);
        if (Response::HTTP_OK !== $response->getStatusCode() || !$this->checkError($response)) {
            return null;
        }

        return self::VALUE_VALID === $response->getContent();
    }

    private function getCurrentRequest(): ?Request
    {
        return $this->stack->getCurrentRequest();
    }

    private function getVerifyParameters(Request $request, string $content, array $options): array
    {
        /** @psalm-var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        $headers = $request->headers;

        return \array_filter(\array_merge([
             'user_ip' => $request->getClientIp(),
             'user_agent' => $headers->get('User-Agent'),
             'referrer' => $headers->get('referer'),
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
}
