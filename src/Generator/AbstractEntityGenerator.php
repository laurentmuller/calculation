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
use App\Traits\JsonResponseTrait;
use App\Traits\LoggerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class to generate entities.
 *
 * @template TEntity of EntityInterface
 */
abstract class AbstractEntityGenerator implements GeneratorInterface
{
    use JsonResponseTrait;
    use LoggerTrait;

    private readonly Generator $generator;

    public function __construct(
        FakerService $service,
        private readonly EntityManagerInterface $manager,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {
        $this->generator = $service->getGenerator();
    }

    #[\Override]
    public function generate(int $count, bool $simulate): JsonResponse
    {
        if ($count <= 0) {
            return $this->jsonFalse(['message' => $this->trans('generate.error.empty')]);
        }

        try {
            $items = [];
            $entities = $this->createEntities($count, $simulate, $this->generator);
            $count = \count($entities);
            if ($count > 0) {
                $items = $this->saveAndMapEntities($entities, $simulate);
            }

            return $this->jsonTrue([
                'items' => $items,
                'count' => $count,
                'simulate' => $simulate,
                'message' => $this->getCountMessage($count),
            ]);
        } catch (\Exception $e) {
            return $this->jsonException(
                exception: $e,
                message: $this->trans('generate.error.failed'),
                logger: $this->getLogger()
            );
        }
    }

    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Create entities.
     *
     * @return TEntity[]
     */
    abstract protected function createEntities(int $count, bool $simulate, Generator $generator): array;

    abstract protected function getCountMessage(int $count): string;

    /**
     * @phpstan-param TEntity $entity
     *
     * @return array<string, string|null>
     */
    abstract protected function mapEntity(EntityInterface $entity): array;

    /**
     * Translate the given message.
     */
    protected function trans(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters);
    }

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
