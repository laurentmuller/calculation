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

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends the FileType to use within the FileInput plugin.
 *
 * @extends AbstractFileTypeExtension<FileType>
 */
class FileTypeExtension extends AbstractFileTypeExtension
{
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $options = $this->updateOptions($form, $options);
        parent::buildView($view, $form, $options);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    /**
     * @phpstan-param FormInterface<array> $form
     *
     * @psalm-param FormInterface $form
     *
     * @phpstan-return array<array-key, mixed>
     *
     * @psalm-suppress MixedAssignment
     */
    protected function updateOptions(FormInterface $form, array $options): array
    {
        $configuration = $form->getParent()?->getConfig();
        if (!$configuration instanceof FormConfigInterface) {
            return $options;
        }

        foreach (['placeholder', 'maxfiles', 'maxsize', 'maxsizetotal'] as $name) {
            if ($configuration->hasOption($name)) {
                $options[$name] = $configuration->getOption($name);
            }
        }

        return $options;
    }
}
