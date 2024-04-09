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

namespace App\Service;

use App\Form\Type\PlainType;
use App\Utils\StringUtils;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to build form for a command.
 *
 * @psalm-import-type CommandType from CommandService
 * @psalm-import-type ArgumentType from CommandService
 * @psalm-import-type OptionType from CommandService
 */
readonly class CommandBuilderService
{
    /**
     * The argument form prefix.
     */
    public const ARGUMENT_PREFIX = 'arguments-';

    /**
     * The option form prefix.
     */
    public const OPTION_PREFIX = 'options-';

    public function __construct(
        private FormFactoryInterface $factory,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Gets field names and default values for the given command.
     *
     * @psalm-param CommandType $command
     *
     * @return array<string, array|string|int|bool|null>
     *
     * @phpstan-ignore-next-line
     */
    public function getData(array $command): array
    {
        $data = [];

        // name
        $data['name'] = $command['name'];

        // arguments
        foreach ($command['definition']['arguments'] as $key => $argument) {
            $name = $this->addPrefix($key, self::ARGUMENT_PREFIX);
            $data[$name] = $argument['default'];
        }

        // options
        foreach ($command['definition']['options'] as $key => $option) {
            $name = $this->addPrefix($key, self::OPTION_PREFIX);
            $data[$name] = $option['default'];
        }

        return $data;
    }

    /**
     * Create a form for the given command.
     *
     * @psalm-param CommandType $command
     *
     * @phpstan-ignore-next-line
     */
    public function getForm(array $command): FormInterface
    {
        $data = $this->getData($command);
        $builder = $this->factory->createBuilder(data: $data);

        // transformer for array
        $transformer = new CallbackTransformer(
            /** @psalm-param string[] $data */
            fn (array $data) => \implode(',', \array_filter($data)),
            fn (?string $data) => StringUtils::isString($data) ? \explode(',', $data) : []
        );

        // name
        $this->addName($builder, $command);

        // arguments
        $this->addArguments($builder, $command, $transformer);

        // options
        $this->addOptions($builder, $command, $transformer);

        return $builder->getForm();
    }

    /**
     * Gets the command parameters from the given data.
     *
     * @param array<string, array|string|int|bool|null> $data
     *
     * @return array<string, array|string|int|bool|null> the command parameters
     */
    public function getParameters(array $data): array
    {
        $parameters = [];
        foreach ($data as $key => $value) {
            if ('name' === $key || null === $value || false === $value || [] === $value) {
                continue;
            }
            if (\str_starts_with($key, self::ARGUMENT_PREFIX)) {
                $key = \substr($key, \strlen(self::ARGUMENT_PREFIX));
            } elseif (\str_starts_with($key, self::OPTION_PREFIX)) {
                $key = '--' . \substr($key, \strlen(self::OPTION_PREFIX));
            }
            $parameters[$key] = $value;
        }

        return $parameters;
    }

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addArgumentBool(FormBuilderInterface $builder, string $key, array $argument): void
    {
        $name = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $builder->add($name, CheckboxType::class, [
            'label' => $argument['name'],
            'help' => $argument['description'],
            'required' => $argument['is_required'],
            'help_html' => true,
            'translation_domain' => false,
        ]);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addArguments(FormBuilderInterface $builder, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['arguments'] as $key => $argument) {
            if ($argument['is_array']) {
                $this->addArgumentText($builder, $key, $argument, $transformer);
                continue;
            }

            $default = $argument['default'];
            if (\is_bool($default)) {
                $this->addArgumentBool($builder, $key, $argument);
                continue;
            }

            if (null === $default || \is_string($default)) {
                $this->addArgumentText($builder, $key, $argument);
            }
        }
    }

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addArgumentText(
        FormBuilderInterface $builder,
        string $key,
        array $argument,
        ?CallbackTransformer $transformer = null
    ): void {
        $name = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $required = $transformer instanceof CallbackTransformer ? false : $argument['is_required'];
        $builder->add($name, TextType::class, [
            'label' => $argument['name'],
            'help' => $argument['description'],
            'required' => $required,
            'help_html' => true,
            'translation_domain' => false,
        ]);
        if ($transformer instanceof CallbackTransformer) {
            $builder->get($name)
                ->addModelTransformer($transformer);
        }
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addName(FormBuilderInterface $builder, array $command): void
    {
        // name
        $builder->add('name', PlainType::class, [
            'label' => $this->translator->trans('command.list.fields.command'),
            'help' => $command['description'],
            'help_html' => true,
            'expanded' => true,
            'translation_domain' => false,
        ]);
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addOptionBool(FormBuilderInterface $builder, string $key, array $option): void
    {
        $name = $this->addPrefix($key, self::OPTION_PREFIX);
        $builder->add($name, CheckboxType::class, [
            'label' => $option['name'],
            'help' => $option['description'],
            'required' => $option['is_value_required'],
            'help_html' => true,
            'translation_domain' => false,
        ]);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addOptions(FormBuilderInterface $builder, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['options'] as $key => $option) {
            if ($option['is_multiple']) {
                $this->addOptionText($builder, $key, $option, $transformer);
                continue;
            }

            $default = $option['default'];
            if (null === $default || \is_string($default)) {
                $this->addOptionText($builder, $key, $option);
                continue;
            }

            if (!$option['accept_value'] || \is_bool($default)) {
                $this->addOptionBool($builder, $key, $option);
            }
        }
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addOptionText(
        FormBuilderInterface $builder,
        string $key,
        array $option,
        ?CallbackTransformer $transformer = null
    ): void {
        $name = $this->addPrefix($key, self::OPTION_PREFIX);
        $required = $transformer instanceof CallbackTransformer ? false : $option['is_value_required'];
        $builder->add($name, TextType::class, [
            'label' => $option['name'],
            'help' => $option['description'],
            'required' => $required,
            'help_html' => true,
            'translation_domain' => false,
        ]);
        if ($transformer instanceof CallbackTransformer) {
            $builder->get($name)
                ->addModelTransformer($transformer);
        }
    }

    private function addPrefix(string $name, string $prefix): string
    {
        return $prefix . $name;
    }
}
