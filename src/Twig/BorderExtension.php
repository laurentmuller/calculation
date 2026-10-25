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

namespace App\Twig;

use App\Constants\CacheAttributes;
use App\Entity\CalculationState;
use App\Interfaces\DisableListenerInterface;
use App\Repository\CalculationStateRepository;
use App\Traits\DisableListenerTrait;
use App\Utils\StringUtils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig extension to output calculation state colors as CSS classes.
 */
#[AsEntityListener(event: Events::postPersist, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
#[AsEntityListener(event: Events::postRemove, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
final class BorderExtension implements DisableListenerInterface
{
    use DisableListenerTrait;

    private const string KEY = 'border_colors_template';

    private const string TEMPLATE = <<<'TWIG'
        .state-border-#id { border-left-color: #color !important; }
        .state-background-#id { background-color: #color; }
        TWIG;

    public function __construct(
        private readonly CalculationStateRepository $repository,
        #[Target(CacheAttributes::CACHE_ASSET)]
        private readonly CacheInterface $cache,
    ) {
    }

    public function deleteCache(): void
    {
        $this->cache->delete(self::KEY);
    }

    #[AsTwigFunction(name: 'css_border', isSafe: ['html'])]
    public function render(): string
    {
        return $this->cache->get(self::KEY, fn (): string => $this->loadTemplate());
    }

    private function loadTemplate(): string
    {
        return \implode(
            StringUtils::NEW_LINE,
            \array_map($this->replace(...), $this->repository->findAll())
        );
    }

    private function replace(CalculationState $state): string
    {
        return \strtr(self::TEMPLATE, [
            '#id' => $state->getId(),
            '#color' => $state->getColor(),
        ]);
    }
}
