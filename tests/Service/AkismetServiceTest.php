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

namespace App\Tests\Service;

use App\Model\HttpClientError;
use App\Service\AbstractHttpClientService;
use App\Service\AkismetService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[CoversClass(AbstractHttpClientService::class)]
#[CoversClass(AkismetService::class)]
class AkismetServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&RequestStack $requestStack;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->requestStack = $this->createMock(RequestStack::class);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyCommentInvalidCode(): void
    {
        $expected_code = 404;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
            ['http_code' => $expected_code]
        );

        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->verifyComment('content');
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyCommentNoRequest(): void
    {
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $service = $this->createService();
        $actual = $service->verifyComment('content');
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyCommentSuccess(): void
    {
        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $service = $this->createService();
        $actual = $service->verifyComment('content');
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyKeyInvalidCode(): void
    {
        $expected_code = 404;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
            ['http_code' => $expected_code]
        );

        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->verifyKey();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyKeyLastError(): void
    {
        $expected_code = 100;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
            [
                'response_headers' => [
                    'x-akismet-alert-code' => $expected_code,
                    'x-akismet-alert-msg' => $expected_message,
                ],
            ]
        );

        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->verifyKey();
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame($expected_code, $actual->getCode());
        self::assertSame($expected_message, $actual->getMessage());
    }

    /**
     * @throws Exception
     */
    public function testVerifyKeyNoRequest(): void
    {
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $service = $this->createService();
        $actual = $service->verifyKey();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     */
    public function testVerifyKeySuccess(): void
    {
        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $response = new MockResponse('valid');
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->verifyKey();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testVerifyLastError(): void
    {
        $expected_code = 100;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
            [
                'response_headers' => [
                    'x-akismet-alert-code' => $expected_code,
                    'x-akismet-alert-msg' => $expected_message,
                ],
            ]
        );

        $request = new Request();
        $this->requestStack
            ->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->verifyComment('content');
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame($expected_code, $actual->getCode());
        self::assertSame($expected_message, $actual->getMessage());
    }

    /**
     * @throws Exception
     */
    private function createService(): AkismetService
    {
        $key = 'fake';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMockTranslator();

        return new AkismetService(
            $key,
            $cache,
            $logger,
            $security,
            $this->requestStack,
            $translator
        );
    }
}
