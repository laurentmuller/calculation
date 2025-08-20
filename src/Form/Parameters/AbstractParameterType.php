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

namespace App\Form\Parameters;

use App\Form\AbstractHelperType;
use App\Form\FormHelper;
use App\Parameter\ParameterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract type for a parameter.
 */
abstract class AbstractParameterType extends AbstractHelperType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->getParameterClass(),
        ]);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $values = $this->getDefaultValues($form);
        if ([] === $values) {
            return;
        }

        $children = $view->children;
        /** @phpstan-var mixed $value */
        foreach ($values as $key => $value) {
            if (!\array_key_exists($key, $children)) {
                continue;
            }

            $child = $children[$key];
            $child->vars['attr'] = \array_merge(
                $child->vars['attr'] ?? [],
                ['data-default' => $this->convertValue($value)]
            );
        }
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addCheckboxType(
        FormHelper $helper,
        string $field,
        string $label,
        ?string $help = null
    ): void {
        $helper->field($field)
            ->label($label)
            ->help($help)
            ->addCheckboxType(inline: true);
    }

    /**
     * @return class-string<ParameterInterface>
     */
    abstract protected function getParameterClass(): string;

    private function convertValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if (\is_bool($value)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @phpstan-param FormInterface<array> $form
     *
     * @psalm-param FormInterface $form
     *
     * @phpstan-return array<string, mixed>
     */
    private function getDefaultValues(FormInterface $form): array
    {
        $config = $form->getRoot()->getConfig();
        $key = $this->getParameterClass()::getCacheKey();
        /** @phpstan-var array<string, array<string, mixed>> $values */
        $values = $config->getOption(AbstractHelperParametersType::DEFAULT_VALUES, []);

        return $values[$key] ?? [];
    }
}
