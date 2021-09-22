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

namespace App\Form\User;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Model\Theme;
use App\Service\ThemeService;
use App\Traits\TranslatorTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to select a theme.
 *
 * @author Laurent Muller
 */
class ThemeType extends AbstractHelperType
{
    use TranslatorTrait;

    /**
     * The backgound choices.
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
     */
    public const FOREGROUND_CHOICES = [
        'theme.foreground.dark' => 'navbar-dark',
        'theme.foreground.light' => 'navbar-light',
    ];

    private ThemeService $service;

    /**
     * Constructor.
     */
    public function __construct(ThemeService $service, TranslatorInterface $translator)
    {
        $this->service = $service;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $this->addThemeField($helper);
        $this->addBackgroundField($helper);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'theme.fields.';
    }

    /**
     * Adds the background field.
     */
    private function addBackgroundField(FormHelper $helper): self
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
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($choices);

        return $this;
    }

    /**
     * Adds the CSS field.
     */
    private function addThemeField(FormHelper $helper): self
    {
        $themes = $this->service->getThemes();
        $choice_attr = function (Theme $choice) {
            return [
                'data-description' => $choice->getDescription(),
                'data-css' => $choice->getCss(),
            ];
        };
        $helper->field('theme')
            ->updateOption('choice_label', 'name')
            ->updateOption('choice_value', 'name')
            ->updateOption('choice_attr', $choice_attr)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($themes);

        return $this;
    }
}
