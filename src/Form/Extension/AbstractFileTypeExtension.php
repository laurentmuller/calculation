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

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base extension for FileType.
 */
abstract class AbstractFileTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->updateAttributes($form, $view->vars['attr'], $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // the number of files
        $resolver->setDefined('maxfiles')
            ->setAllowedTypes('maxfiles', 'integer');

        // the size of each file
        $resolver->setDefined('maxsize')
            ->setAllowedTypes('maxsize', ['integer', 'string']);

        // the total size of all files
        $resolver->setDefined('maxsizetotal')
            ->setAllowedTypes('maxsizetotal', ['integer', 'string']);

        // the placeholder
        $resolver->setDefined('placeholder')
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setDefault('placeholder', 'filetype.placeholder');
    }

    /**
     * Updates attributes.
     *
     * @param FormInterface $form       the form
     * @param array         $attributes the attributes to update
     * @param array         $options    the options
     */
    protected function updateAttributes(FormInterface $form, array &$attributes, array &$options): void
    {
        if (isset($options['placeholder'])) {
            /** @var string $placeholder */
            $placeholder = $options['placeholder'];
            $attributes['placeholder'] = $placeholder;
        }
        if (isset($options['maxfiles'])) {
            $value = (int) $options['maxfiles'];
            if ($value > 1) {
                $attributes['maxfiles'] = $value;
            }
        }
        if (isset($options['maxsize'])) {
            /** @var string|int $maxsize */
            $maxsize = $options['maxsize'];
            $attributes['maxsize'] = $this->normalizeSize($maxsize);
        }
        if (isset($options['maxsizetotal'])) {
            /** @var string|int $maxsizetotal */
            $maxsizetotal = $options['maxsizetotal'];
            $attributes['maxsizetotal'] = $this->normalizeSize($maxsizetotal);
        }
    }

    /**
     * Normalize the given size.
     *
     * @param int|string $size the size to normalize
     *
     * @return int|null the normalized size
     *
     * @throws InvalidOptionsException if the $size can not be parsed
     *
     * @see https://symfony.com/doc/current/reference/constraints/File.html#maxsize
     */
    private function normalizeSize(int|string $size): ?int
    {
        if (empty($size)) {
            return null;
        }

        $factors = [
            'k' => 1_000,
            'ki' => 1 << 10,
            'm' => 1_000_000,
            'mi' => 1 << 20,
        ];

        if (\ctype_digit((string) $size)) {
            return (int) $size;
        }

        $matches = [];
        if (\preg_match('/^(\d++)(' . \implode('|', \array_keys($factors)) . ')$/i', (string) $size, $matches)) {
            return (int) $matches[1] * $factors[\strtolower($matches[2])];
        }
        throw new InvalidOptionsException("\"$size\" is not a valid size.");
    }
}
