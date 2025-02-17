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
use App\Interfaces\EntityInterface;
use App\Interfaces\GeneratorInterface;
use App\Service\FakerService;
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Class to generate entities.
 *
 * @template TEntity of EntityInterface
 */
abstract class AbstractEntityGenerator implements GeneratorInterface, ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    private readonly Generator $generator;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        FakerService $fakerService
    ) {
        $this->generator = $fakerService->getGenerator();
    }

    #[\Override]
    public function generate(int $count, bool $simulate): JsonResponse
    {
        if ($count <= 0) {
            return new JsonResponse([
                'result' => false,
                'message' => $this->trans('generate.error.empty'),
            ]);
        }

        try {
            $items = [];
            $entities = $this->createEntities($count, $simulate, $this->generator);
            $count = \count($entities);
            if ($count > 0) {
                $items = $this->saveAndMapEntities($entities, $simulate);
            }

            return new JsonResponse([
                'result' => true,
                'items' => $items,
                'count' => $count,
                'simulate' => $simulate,
                'message' => $this->getCountMessage($count),
            ]);
        } catch (\Exception $e) {
            $message = $this->trans('generate.error.failed');
            $context = $this->getExceptionContext($e);
            $this->logError($message, $context);

            return new JsonResponse([
                'result' => false,
                'message' => $message,
                'exception' => $context,
            ]);
        }
    }

    /**
     * Create entities.
     *
     * @return TEntity[]
     */
    abstract protected function createEntities(int $count, bool $simulate, Generator $generator): array;

    abstract protected function getCountMessage(int $count): string;

    /**
     * @psalm-param TEntity $entity
     *
     * @return array<string, mixed>
     */
    abstract protected function mapEntity(EntityInterface $entity): array;

    /**
     * @param TEntity[] $entities
     */
    private function saveAndMapEntities(array $entities, bool $simulate): array
    {
        $items = [];
        foreach ($entities as $entity) {
            if (!$simulate) {
                $this->manager->persist($entity);
            }
            $items[] = $this->mapEntity($entity);
        }
        if (!$simulate) {
            $this->manager->flush();
        }

        return $items;
    }
}
