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

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A form type to display a captcha image.
 *
 * @author Laurent Muller
 */
class CaptchaImageType extends AbstractType
{
    private UrlGeneratorInterface $generator;

    /**
     * Constructor.
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars = \array_replace($view->vars, [
            'image' => $options['image'],
            'remote' => $options['remote'],
            'refresh' => $options['refresh'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'remote' => $this->generator->generate('ajax_captcha_validate'),
            'refresh' => $this->generator->generate('ajax_captcha_image'),
            'attr' => [
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'class' => 'text-lowercase',
            ],
        ]);

        $resolver->setRequired('image')
            ->setAllowedTypes('image', 'string')
            ->setAllowedTypes('remote', ['null', 'string'])
            ->setAllowedTypes('refresh', ['null', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return TextType::class;
    }
}
