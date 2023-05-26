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
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends the country type with flag.
 *
 * @extends AbstractType<CountryType>
 */
class CountryFlagType extends AbstractType
{
    private const FLAG_CLASS = 'flag-emoji';

    public function __construct(private readonly CountryFlagService $service)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaultCode = CountryFlagService::getDefaultCode();
        $resolver->setDefaults([
            'choice_loader' => fn (Options $options) => ChoiceList::lazy($this, fn () => $this->loadChoices($options)),
            'attr' => ['class' => self::FLAG_CLASS],
            'preferred_choices' => [$defaultCode],
            'choice_translation_locale' => null,
            'empty_data' => $defaultCode,
            'only_flag' => false,
        ]);
        $resolver->setAllowedTypes('choice_translation_locale', ['string', 'null']);
        $resolver->setAllowedTypes('only_flag', 'boolean');
    }

    public function getParent(): ?string
    {
        return CountryType::class;
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function loadChoices(Options $options): array
    {
        /** @psalm-var string|null $locale */
        $locale = $options['choice_translation_locale'];
        /** @psalm-var bool $flagOnly */
        $flagOnly = $options['only_flag'];

        return $this->service->getChoices($locale, $flagOnly);
    }
}
