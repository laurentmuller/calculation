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

namespace App\Tests\Translator;

use App\Translator\BingTranslatorService;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Cache\CacheInterface;

#[CoversClass(TranslatorFactory::class)]
class TranslatorFactoryTest extends TestCase
{
    private MockObject&RequestStack $requestStack;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($session);
    }

    /**
     * @throws Exception
     */
    public function testExist(): void
    {
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        self::assertTrue($factory->exists(BingTranslatorService::class));
        self::assertFalse($factory->exists('fake'));
    }

    /**
     * @throws Exception
     */
    public function testFind(): void
    {
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        self::assertNotNull($factory->find(BingTranslatorService::class));
        self::assertNull($factory->find('fake'));
    }

    /**
     * @throws Exception
     */
    public function testGetServiceInvalid(): void
    {
        self::expectException(ServiceNotFoundException::class);
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        $factory->getService('fake');
    }

    /**
     * @throws Exception
     */
    public function testGetServiceValid(): void
    {
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        self::assertNotNull($factory->getService(BingTranslatorService::class));
    }

    /**
     * @throws Exception
     */
    public function testGetSessionServiceWithInvalidValue(): void
    {
        $this->requestStack->getSession()
            ->set('translator_service', 'fake');

        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        self::assertNotNull($factory->getSessionService());
    }

    /**
     * @throws Exception
     */
    public function testGetSessionServiceWithoutValue(): void
    {
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        self::assertNotNull($factory->getSessionService());
    }

    /**
     * @throws Exception
     */
    public function testGetTranslators(): void
    {
        $translators = [$this->createBingTranslator()];
        $factory = $this->createFactory($translators);
        $actual = $factory->getTranslators();
        self::assertSame($translators, $actual);
    }

    /**
     * @throws Exception
     */
    private function createBingTranslator(): BingTranslatorService
    {
        $key = 'key';
        $cache = $this->createMock(CacheInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new BingTranslatorService($key, $cache, $logger);
    }

    /**
     * @param array<TranslatorServiceInterface> $translators
     */
    private function createFactory(array $translators): TranslatorFactory
    {
        $factory = new TranslatorFactory($translators);
        $factory->setRequestStack($this->requestStack);

        return $factory;
    }
}
