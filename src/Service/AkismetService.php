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

use App\Traits\TranslatorTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to check spams with the Akismet.
 *
 * @see https://akismet.com/
 */
class AkismetService extends AbstractHttpClientService
{
    use TranslatorTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://%s.rest.akismet.com/1.1/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'akismet_key';

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
     * The value returned when the API key is invalid.
     */
    private const VALUE_INVALID = 'invalid';

    /**
     * The value returned when the comment is a spam.
     */
    private const VALUE_TRUE = 'true';

    /**
     * The value returned when the API key is valid.
     */
    private const VALUE_VALID = 'valid';

    private readonly string $endpoint;

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key parameter is not defined
     * @throws \InvalidArgumentException  if the API key is null or empty
     */
    public function __construct(ParameterBagInterface $params, CacheItemPoolInterface $adapter, bool $isDebug, private readonly RequestStack $stack, private readonly Security $security, TranslatorInterface $translator)
    {
        /** @var string $key */
        $key = $params->get(self::PARAM_KEY);
        parent::__construct($adapter, $isDebug, $key);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
        $this->translator = $translator;
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
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
     */
    public function verifyComment(string $content, array $options = []): bool
    {
        if (null !== ($request = $this->stack->getCurrentRequest())) {
            /** @var \App\Entity\User $user */
            $user = $this->security->getUser();
            $headers = $request->headers;

            $body = \array_merge([
                'user_ip' => $request->getClientIp(),
                'user_agent' => $headers->get('User-Agent') ?? '',
                'referrer' => $headers->get('referer') ?? '',

                'comment_content' => $content,
                'comment_type' => 'contact-form',
                'comment_author' => $user->getUserIdentifier(),
                'comment_author_email' => $user->getEmail(),

                'blog' => $request->getSchemeAndHttpHost(),
                'blog_lang' => $request->getLocale(),
                'blog_charset' => 'UTF-8',
                'is_test' => true,
            ], $options);

            $response = $this->requestPost(self::URI_COMMENT_CHECK, [
                self::BODY => $body,
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                $this->checkError($response);

                return true;
            }

            $content = $response->getContent();
            switch ($content) {
                case self::VALUE_TRUE:
                    return true;
                case self::VALUE_FALSE:
                    return false;
                case self::VALUE_INVALID:
                default:
                    $this->checkError($response);

                    return true;
            }
        }

        return true;
    }

    /**
     * Verify that API key is valid.
     *
     * @return bool true if valid; false otherwise
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function verifyKey(): bool
    {
        // already saved?
        /** @psalm-var mixed $verified */
        $verified = $this->getUrlCacheValue(self::URI_VERIFY);
        if (\is_bool($verified)) {
            return $verified;
        }

        if (null !== ($request = $this->stack->getCurrentRequest())) {
            $body = [
                'key' => $this->key,
                'blog' => $request->getSchemeAndHttpHost(),
            ];

            $response = $this->requestPost(self::URI_VERIFY, [
                self::BODY => $body,
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                $this->checkError($response);

                return false;
            }

            $verified = self::VALUE_VALID === $response->getContent();
            $this->setUrlCacheValue(self::URI_VERIFY, $verified);

            return $verified;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => $this->endpoint];
    }

    /**
     * Checks response error.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function checkError(ResponseInterface $response): void
    {
        $headers = $response->getHeaders();
        $code = (int) ($headers['X-akismet-alert-code'][0] ?? 0);
        if (0 !== $code) {
            $message = $this->trans((string) $code, [], 'askimet');
            if ($message === (string) $code) {
                $message = $headers['X-akismet-alert-msg'][0] ?? $this->trans('unknown', [], 'askimet');
            }
            $this->setLastError($code, $message);
        }
    }
}
