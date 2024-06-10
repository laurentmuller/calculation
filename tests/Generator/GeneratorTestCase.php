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

namespace App\Tests\Generator;

use App\Generator\AbstractEntityGenerator;
use App\Service\FakerService;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template TGenerator of AbstractEntityGenerator
 */
abstract class GeneratorTestCase extends KernelServiceTestCase
{
    use DatabaseTrait;

    protected FakerService $fakerService;
    protected LoggerInterface $logger;
    protected EntityManagerInterface $manager;
    protected TranslatorInterface $translator;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->manager = $this->getService(EntityManagerInterface::class);
        $this->fakerService = $this->getService(FakerService::class);
    }

    protected static function assertValidateResponse(JsonResponse $actual, bool $expected, int $count): array
    {
        $content = $actual->getContent();
        self::assertIsString($content);

        try {
            $actual = StringUtils::decodeJson($content);
            self::assertArrayHasKey('result', $actual);
            self::assertSame($expected, $actual['result']);
            if ($count <= 0) {
                return $actual;
            }

            self::assertArrayHasKey('count', $actual);
            self::assertSame($count, $actual['count']);
            self::assertArrayHasKey('items', $actual);
            self::assertIsArray($actual['items']);
            self::assertCount($count, $actual['items']);

            return $actual;
        } catch (\InvalidArgumentException $e) {
            self::fail($e->getMessage());
        }
    }

    /**
     * @psalm-return TGenerator
     */
    abstract protected function createGenerator(): AbstractEntityGenerator;

    /**
     * @psalm-param TGenerator $generator
     *
     * @psalm-return TGenerator
     */
    protected function updateGenerator(AbstractEntityGenerator $generator): AbstractEntityGenerator
    {
        $generator->setTranslator($this->translator);
        $generator->setLogger($this->logger);

        return $generator;
    }
}
