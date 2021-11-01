<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class to generate entities.
 *
 * @author Laurent Muller
 */
abstract class AbstractEntityGenerator implements GeneratorInterface
{
    use LoggerTrait;
    use TranslatorTrait;

    protected Generator $generator;
    protected EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager, FakerService $fakerService, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->generator = $fakerService->getGenerator();
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(int $count, bool $simulate): JsonResponse
    {
        return $this->generateEntities($count, $simulate, $this->manager, $this->generator);
    }

    /**
     * Generate entities.
     */
    abstract protected function generateEntities(int $count, bool $simulate, EntityManagerInterface $manager, Generator $generator): JsonResponse;
}