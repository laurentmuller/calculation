<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form;

use App\Entity\Theme;
use App\Service\ThemeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to select a theme.
 *
 * @author Laurent Muller
 */
class ThemeType extends AbstractType
{
    /**
     * The backgound choices.
     *
     * @var array
     */
    public const BACKGROUND_CHOICES = [
        'theme.background.dark' => 'bg-dark',
        'theme.background.light' => 'bg-light',
        'theme.background.white' => 'bg-white',
        'theme.background.primary' => 'bg-primary',
        'theme.background.secondary' => 'bg-secondary',
        'theme.background.success' => 'bg-success',
        'theme.background.danger' => 'bg-danger',
        'theme.background.warning' => 'bg-warning',
        'theme.background.info' => 'bg-info',
    ];

    /**
     * The navigation bar choices.
     *
     * @var array
     */
    public const FOREGROUND_CHOICES = [
        'theme.foreground.dark' => 'navbar-dark',
        'theme.foreground.light' => 'navbar-light',
    ];

    /**
     * @var ThemeService
     */
    private $service;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ThemeService        $service    the service to get themes
     * @param TranslatorInterface $translator the translator service
     */
    public function __construct(ThemeService $service, TranslatorInterface $translator)
    {
        $this->service = $service;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);
        $this->addThemeField($helper)
            ->addBackgroundField($helper);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * Adds the CSS field.
     */
    protected function addThemeField(FormHelper $helper): self
    {
        $themes = $this->service->getThemes();
        $choice_attr = function (Theme $choice, $key, $value) {
            return [
                'data-description' => $choice->getDescription(),
            ];
        };
        $helper->field('theme')
            ->label('theme.fields.theme')
            ->updateOption('choice_label', 'name')
            ->updateOption('choice_value', 'name')
            ->updateOption('choice_attr', $choice_attr)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($themes);

        return $this;
    }

    /**
     * Adds the background field.
     */
    protected function addBackgroundField(FormHelper $helper): self
    {
        // concat
        $choices = [];
        foreach (self::BACKGROUND_CHOICES as $keyBackground => $valueBackground) {
            foreach (self::FOREGROUND_CHOICES as $keyForeground => $valueForeground) {
                $key = $this->trans($keyBackground) . ' - ' . $this->trans($keyForeground);
                $value = "{$valueForeground} {$valueBackground}";
                $choices[$key] = $value;
            }
        }

        // remove uncontrasted values
        $choices = \array_diff($choices, ['navbar-light bg-dark', 'navbar-dark bg-light', 'navbar-dark bg-white']);

        $helper->field('background')
            ->label('theme.fields.background')
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($choices);

        return $this;
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id);
    }
}
