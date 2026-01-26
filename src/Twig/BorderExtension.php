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

use App\Entity\CalculationState;
use App\Interfaces\DisableListenerInterface;
use App\Repository\CalculationStateRepository;
use App\Traits\DisableListenerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Twig extension to output calculation state borders CSS.
 */
#[AsEntityListener(event: Events::postPersist, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
#[AsEntityListener(event: Events::postRemove, method: 'deleteCache', lazy: true, entity: CalculationState::class)]
final class BorderExtension implements DisableListenerInterface
{
    use DisableListenerTrait;

    private const string KEY_TEMPLATE = 'border_colors_template';

    public function __construct(
        private readonly CalculationStateRepository $repository,
        private readonly CacheInterface $cache,
    ) {
    }

    public function deleteCache(): void
    {
        $this->cache->delete(self::KEY_TEMPLATE);
    }

    #[AsTwigFunction(name: 'css_border', needsEnvironment: true)]
    public function render(Environment $twig): string
    {
        return $this->cache->get(self::KEY_TEMPLATE, fn (): string => $this->loadTemplate($twig));
    }

    /**
     * @throws Error
     */
    private function loadTemplate(Environment $twig): string
    {
        return $twig->render(
            'macros/_border_colors.css.twig',
            ['states' => $this->repository->findAll()]
        );
    }
}
