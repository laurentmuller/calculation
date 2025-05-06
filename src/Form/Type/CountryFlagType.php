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

namespace App\Form\Type;

use App\Service\CountryFlagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends the country type with a flag.
 *
 * @extends AbstractType<CountryType>
 */
class CountryFlagType extends AbstractType
{
    private const FLAG_CLASS = 'flag-emoji';

    public function __construct(private readonly CountryFlagService $service)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaultCode = CountryFlagService::getDefaultCode();
        $loader = fn (Options $options): ChoiceLoaderInterface => $this->getChoiceLoader($options);

        $resolver->setDefaults([
            'attr' => ['class' => self::FLAG_CLASS],
            'choice_loader' => $loader,
            'empty_data' => $defaultCode,
            'preferred_choices' => [$defaultCode],
            'duplicate_preferred_choices' => false,
            'only_flag' => false,
            'separator' => '─────────────────────────────',
        ])->setAllowedTypes('choice_translation_locale', ['string', 'null'])
            ->setAllowedTypes('only_flag', 'boolean');
    }

    #[\Override]
    public function getParent(): string
    {
        return CountryType::class;
    }

    /**
     * @phpstan-param Options<array> $options
     *
     * @psalm-param Options $options
     */
    private function getChoiceLoader(Options $options): ChoiceLoaderInterface
    {
        return ChoiceList::lazy($this, fn (): array => $this->loadChoices($options));
    }

    /**
     * @psalm-param Options $options
     *
     * @phpstan-param Options<array> $options
     */
    private function loadChoices(Options $options): array
    {
        /** @phpstan-var string|null $locale */
        $locale = $options['choice_translation_locale'];
        /** @phpstan-var bool $flagOnly */
        $flagOnly = $options['only_flag'];

        return $this->service->getChoices($locale, $flagOnly);
    }
}
