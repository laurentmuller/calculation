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

use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom color type with a drop-down palette.
 *
 * @extends AbstractType<ColorType>
 */
class CustomColorType extends AbstractType
{
    /**
     * @param string $colorsPath the JSON file containing an array of names/colors
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/colors.json')]
        private readonly string $colorsPath
    ) {
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['colors'] = FileUtils::decodeJson($this->colorsPath);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'color-picker'],
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return ColorType::class;
    }
}
