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

namespace App\Form\User;

use App\Enums\Theme;
use App\Form\FormHelper;
use App\Form\Parameters\AbstractParametersType;
use App\Service\ApplicationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type for user parameters.
 */
class UserParametersType extends AbstractParametersType
{
    /**
     * The theme field name.
     */
    final public const THEME_FIELD = 'theme';

    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator, ApplicationService $service)
    {
        parent::__construct($security, $translator, $service->getProperties());
    }

    protected function addSections(FormHelper $helper): void
    {
        $this->addDisplaySection($helper);
        $this->addMessageSection($helper);
        $this->addHomePageSection($helper);
        $this->addOptionsSection($helper);
        $this->addThemeSection($helper);
    }

    private function addThemeSection(FormHelper $helper): void
    {
        $value = Theme::getDefault()->value;
        $helper->field(self::THEME_FIELD)
            ->label(false)
            ->updateOption('expanded', true)
            ->updateOption('choice_attr', fn (Theme $theme) => $this->updateOptions($theme, $value))
            ->addEnumType(Theme::class);
    }

    private function updateOptions(Theme $theme, string $default): array
    {
        return [
            'data-default' => $default,
            'help' => $theme->getHelp(),
        ];
    }
}
