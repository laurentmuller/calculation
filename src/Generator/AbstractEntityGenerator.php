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

namespace App\Generator;

use App\Faker\Generator;
use App\Interfaces\GeneratorInterface;
use App\Service\FakerService;
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Class to generate entities.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractEntityGenerator implements GeneratorInterface, ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The faker generator.
     */
    protected Generator $generator;

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, FakerService $fakerService)
    {
        $this->generator = $fakerService->getGenerator();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    public function generate(int $count, bool $simulate): JsonResponse
    {
        try {
            return $this->generateEntities($count, $simulate, $this->manager, $this->generator);
        } catch (\Exception $e) {
            $message = $this->trans('generate.error.failed');
            $context = Utils::getExceptionContext($e);
            $this->logError($message, $context);

            return new JsonResponse([
                'result' => false,
                'message' => $message,
                'exception' => $context,
            ]);
        }
    }

    /**
     * Generate entities.
     */
    abstract protected function generateEntities(int $count, bool $simulate, EntityManagerInterface $manager, Generator $generator): JsonResponse;
}
