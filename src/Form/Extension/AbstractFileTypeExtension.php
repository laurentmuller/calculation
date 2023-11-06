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
 *
 * @psalm-template T of \Symfony\Component\Form\FormTypeInterface
 *
 * @extends AbstractTypeExtension<T>
 */
abstract class AbstractFileTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attributes = $this->updateAttributes($options, $view->vars['attr'] ?? []);
        $view->vars['attr'] = $attributes;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // the number of files
        $resolver->setDefined('maxfiles')
            ->setAllowedTypes('maxfiles', 'int');

        // the size of each file
        $resolver->setDefined('maxsize')
            ->setAllowedTypes('maxsize', ['int', 'string']);

        // the total size of all files
        $resolver->setDefined('maxsizetotal')
            ->setAllowedTypes('maxsizetotal', ['int', 'string']);

        // the placeholder
        $resolver->setDefined('placeholder')
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setDefault('placeholder', 'filetype.placeholder');
    }

    /**
     * Updates attributes.
     */
    protected function updateAttributes(array $options, array $attributes): array
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

        return $attributes;
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

        if (\is_string($size) && \ctype_digit($size)) {
            return (int) $size;
        }

        $matches = [];
        if (\preg_match('/^(\d++)(' . \implode('|', \array_keys($factors)) . ')$/i', (string) $size, $matches)) {
            return (int) $matches[1] * $factors[\strtolower($matches[2])];
        }

        throw new InvalidOptionsException("\"$size\" is not a valid size.");
    }
}
