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

use App\Model\CommandResult;
use App\Utils\StringUtils;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get or run commands.
 *
 * @phpstan-type ArgumentType = array{
 *     name: string,
 *     is_required: bool,
 *     is_array: bool,
 *     description: string,
 *     default: array|scalar|null,
 *     display: string,
 *     arguments: string}
 * @phpstan-type OptionType = array{
 *     name: string,
 *     shortcut: string,
 *     name_shortcut: string,
 *     accept_value: bool,
 *     is_value_required: bool,
 *     is_multiple: bool,
 *     description: string,
 *     default: array|scalar|null,
 *     display: string,
 *     arguments: string}
 * @phpstan-type CommandType = array{
 *      name: string,
 *      description: string,
 *      usage: string[],
 *      help: string,
 *      hidden: bool,
 *      arguments: array<string, ArgumentType>,
 *      options: array<string, OptionType>}
 * @phpstan-type CommandSourceType = array{
 *      name: string,
 *      description: string,
 *      usage: string[],
 *      help: string,
 *      hidden: bool,
 *      definition: array{
 *          arguments: array<string, ArgumentType>,
 *          options: array<string, OptionType>}
 *     }
 */
class CommandService implements \Countable
{
    /**
     * The group name for commands without name space.
     */
    public const GLOBAL_GROUP = '_global';

    private const CONSOLE_REPLACE = [
        // development
        '/development/public/index.php',
        '/bin/console/command',
        // production
        '/calculation/public/index.php',
        // local development
        '/index.php/command/execute',
        '/index.php/command/pdf',
        '/index.php/command',
    ];

    private const HELP_REPLACE = [
        '<info>' => '<span class="text-success">',
        '<error>' => '<span class="text-danger">',
        '<comment>' => '<span class="text-secondary">',
        '<fg=yellow>' => '<span class="text-secondary">',
        '</info>' => '</span>',
        '</error>' => '</span>',
        '</comment>' => '</span>',
        '</>' => '</span>',
    ];

    private const HREF_REPLACE = [
        '/(<href=)(.*?)>(.*?)(<\/>)/m' => '<a href="$2" target="_blank" rel="noopener noreferrer">$3</a>',
    ];

    private const USAGE_REPLACE = [
        '/(\[.*)( \[--\])/m' => '[options]$2',
    ];

    public function __construct(
        private readonly KernelInterface $kernel,
        #[Target('calculation.command')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the number of commands.
     */
    #[\Override]
    public function count(): int
    {
        return \count($this->getCommands());
    }

    /**
     * Execute the given command.
     *
     * @param string $command         the command name to execute
     * @param array  $parameters      the command parameters
     * @param bool   $catchExceptions sets whether to catch exceptions or not during command execution
     * @param bool   $catchErrors     sets whether to catch errors or not during command execution
     *
     * @return CommandResult the result of the execution
     *
     * @throws \Exception if the running command fail. Bypass when <code>$catchExceptions</code> is <code>false</code>.
     */
    public function execute(
        string $command,
        array $parameters = [],
        bool $catchExceptions = true,
        bool $catchErrors = false,
    ): CommandResult {
        $parameters = ['command' => $command] + $parameters;
        $input = new ArrayInput($parameters);
        $output = new BufferedOutput();

        $application = $this->createApplication($catchExceptions, $catchErrors);
        $status = $application->run($input, $output);
        $content = $this->updateOutput($output->fetch());
        unset($application);

        return new CommandResult($status, $content);
    }

    /**
     * Gets the first command.
     *
     * @phpstan-return CommandType|false
     */
    public function first(): array|false
    {
        $commands = $this->getCommands();

        return \reset($commands);
    }

    /**
     * Gets the command for the given name.
     *
     * @phpstan-return CommandType|null
     */
    public function getCommand(string $name): ?array
    {
        return $this->getCommands()[$name] ?? null;
    }

    /**
     * Gets all commands.
     *
     * @phpstan-return array<string, CommandType>
     */
    public function getCommands(): array
    {
        return $this->cache->get('cache.command.service', $this->loadCommands(...));
    }

    /**
     * Gets all commands grouped by name space.
     *
     * @param string $default the default name for commands without a name space
     *
     * @phpstan-return array<string, CommandType[]>
     */
    public function getGroupedCommands(string $default = self::GLOBAL_GROUP): array
    {
        return $this->getGroupedValues($default, static fn (array $command): array => $command);
    }

    /**
     * Gets all command names grouped by name space.
     *
     * @param string $default the default name for commands without a name space
     *
     * @return array<string, string[]>
     */
    public function getGroupedNames(string $default = self::GLOBAL_GROUP): array
    {
        return $this->getGroupedValues($default, static fn (array $command): string => $command['name']);
    }

    /**
     * Returns if the given command name exists.
     */
    public function hasCommand(string $name): bool
    {
        return \array_key_exists($name, $this->getCommands());
    }

    private function createApplication(bool $catchExceptions, bool $catchErrors): Application
    {
        $application = new Application($this->kernel);
        $application->setCatchExceptions($catchExceptions);
        $application->setCatchErrors($catchErrors);
        $application->setAutoExit(false);

        return $application;
    }

    private function encodeDefaultValue(mixed $default): string
    {
        if (false === $default || null === $default) {
            return '';
        }

        if ([] === $default) {
            return '[]';
        }

        return \str_replace('\\\\', '\\', (string) \json_encode($default, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    }

    /**
     * @phpstan-param ArgumentType $argument
     */
    private function getArgumentHelp(array $argument): string
    {
        $values = [];
        if ($argument['is_required']) {
            $values[] = '<span class="text-danger">required</span>';
        }
        $display = $argument['display'];
        if ('[]' === $display || $argument['is_array']) {
            $values[] = '<span class="text-secondary">multiple values allowed</span>';
        } elseif ('' !== $display) {
            $values[] = \sprintf('default: <span class="text-secondary">%s</span>', $display);
        }
        if ([] === $values) {
            return '';
        }

        return \sprintf('(%s)', \implode(', ', $values));
    }

    /**
     * @template TValue
     *
     * @param callable(CommandType):TValue $callback
     *
     * @return array<string, TValue[]>
     */
    private function getGroupedValues(string $default, callable $callback): array
    {
        return \array_reduce(
            $this->getCommands(),
            /**
             * @phpstan-param array<string, TValue[]> $carry
             * @phpstan-param CommandType $command
             */
            function (array $carry, array $command) use ($default, $callback): array {
                $group = $this->getGroupName($command, $default);
                $carry[$group][] = \call_user_func($callback, $command);

                return $carry;
            },
            []
        );
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function getGroupName(array $command, string $default): string
    {
        $name = $command['name'];
        $group = \explode(':', $name)[0];

        return $name === $group ? $default : $group;
    }

    /**
     * @phpstan-param OptionType $option
     */
    private function getOptionHelp(array $option): string
    {
        $values = [];
        if ($option['is_value_required']) {
            $values[] = '<span class="text-danger">required</span>';
        }
        $display = $option['display'];
        if ('[]' === $display || $option['is_multiple']) {
            $values[] = '<span class="text-secondary">multiple values allowed</span>';
        } elseif ('' !== $display) {
            $values[] = \sprintf('default: <span class="text-secondary">%s</span>', $display);
        }
        if ([] === $values) {
            return '';
        }

        return \sprintf('(%s)', \implode(', ', $values));
    }

    private function getOptionNameAndShortcut(string $name, string $shortcut): string
    {
        $format = '' === $shortcut ? '%4s%s' : '%s, %s';

        return \sprintf($format, $shortcut, $name);
    }

    /**
     * @phpstan-return array<string, CommandType>
     *
     * @throws \Exception
     */
    private function loadCommands(): array
    {
        $result = $this->execute('list', ['--format' => 'json']);
        if (!$result->isSuccess()) {
            return [];
        }

        // remove carriage return
        $content = $this->replaceCarriageReturn($result->content);

        /** @phpstan-var array{commands: CommandSourceType[]} $decoded */
        $decoded = StringUtils::decodeJson($content);
        $commands = \array_reduce(
            $decoded['commands'],
            /**
             * @phpstan-param array<string, CommandType> $carry
             * @phpstan-param CommandSourceType $command
             */
            fn (array $carry, array $command): array => $command['hidden'] ? $carry : $carry + [$command['name'] => $this->updateCommand($command)],
            []
        );
        \ksort($commands);

        return $commands;
    }

    private function replaceCarriageReturn(string $subject): string
    {
        return \str_replace("\r", '', $subject);
    }

    private function replaceConsole(string $subject): string
    {
        return \str_replace(self::CONSOLE_REPLACE, 'bin/console', $subject);
    }

    /**
     * @phpstan-param array<string, ArgumentType> $arguments
     *
     * @phpstan-return array<string, ArgumentType>
     */
    private function updateArguments(array $arguments): array
    {
        foreach ($arguments as &$argument) {
            $argument['description'] = $this->updateHelp($argument['description']);
            $argument['display'] = $this->encodeDefaultValue($argument['default']);
            $argument['arguments'] = $this->getArgumentHelp($argument);
        }

        return $arguments;
    }

    /**
     * @phpstan-param CommandSourceType $command
     *
     * @phpstan-return CommandType
     */
    private function updateCommand(array $command): array
    {
        $command['arguments'] = $this->updateArguments($command['definition']['arguments']);
        $command['options'] = $this->updateOptions($command['definition']['options']);
        $command['help'] = $command['help'] === $command['description'] ? '' : $this->updateHelp($command['help']);
        if ([] === $command['arguments']) {
            $command['usage'] = [\sprintf('%s [options]', $command['name'])];
        } else {
            $command['usage'] = StringUtils::pregReplaceAll(self::USAGE_REPLACE, $command['usage']);
        }
        unset($command['definition']);

        return $command;
    }

    private function updateHelp(string $help): string
    {
        if (!StringUtils::isString($help)) {
            return $help;
        }

        $help = StringUtils::pregReplaceAll(self::HREF_REPLACE, $help);
        $help = StringUtils::replace(self::HELP_REPLACE, $help);

        return $this->replaceConsole($help);
    }

    /**
     * @phpstan-param array<string, OptionType> $options
     *
     * @phpstan-return array<string, OptionType>
     */
    private function updateOptions(array $options): array
    {
        foreach ($options as &$option) {
            $option['description'] = $this->updateHelp($option['description']);
            $option['display'] = $this->encodeDefaultValue($option['default']);
            $option['arguments'] = $this->getOptionHelp($option);
            $option['name_shortcut'] = $this->getOptionNameAndShortcut($option['name'], $option['shortcut']);
        }

        return $options;
    }

    private function updateOutput(string $output): string
    {
        $output = $this->replaceConsole($output);
        $output = $this->replaceCarriageReturn($output);
        $lines = \array_map(\rtrim(...), \explode(StringUtils::NEW_LINE, \rtrim($output)));

        return \implode(StringUtils::NEW_LINE, $lines);
    }
}
