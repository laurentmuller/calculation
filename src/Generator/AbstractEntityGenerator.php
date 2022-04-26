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
use App\Traits\LoggerTrait;
use App\Traits\TranslatorTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class to generate entities.
 */
abstract class AbstractEntityGenerator implements LoggerAwareInterface, GeneratorInterface
{
    use LoggerTrait;
    use TranslatorTrait;

    /**
     * The faker generator.
     */
    protected Generator $generator;

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, FakerService $fakerService, TranslatorInterface $translator)
    {
        $this->generator = $fakerService->getGenerator();
        $this->setTranslator($translator);
    }

    /**
     * {@inheritDoc}
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
