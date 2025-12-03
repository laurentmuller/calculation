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
 * @phpstan-type InputType= array{
 *     name: string,
 *     shortcut: string,
 *     shortcutName: string,
 *     description: string,
 *     isRequired: bool,
 *     isArray: bool,
 *     isAcceptValue: bool,
 *     default: array|scalar|null,
 *     display: string,
 *     extra: string}
 * @phpstan-type CommandType = array{
 *      name: string,
 *      description: string,
 *      usage: string[],
 *      help: string,
 *      hidden: bool,
 *      arguments: array<string, InputType>,
 *      options: array<string, InputType>}
 * @phpstan-type ArgumentSourceType = array{
 *      name: string,
 *      is_required: bool,
 *      is_array: bool,
 *      description: string,
 *      default: array|scalar|null}
 * @phpstan-type OptionSourceType = array{
 *      name: string,
 *      shortcut: string,
 *      accept_value: bool,
 *      is_value_required: bool,
 *      is_multiple: bool,
 *      description: string,
 *      default: array|scalar|null}
 * @phpstan-type CommandSourceType = array{
 *      name: string,
 *      description: string,
 *      usage: string[],
 *      help: string,
 *      hidden: bool,
 *      definition: array{
 *          arguments: array<string, ArgumentSourceType>,
 *          options: array<string, OptionSourceType>}
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
     * @phpstan-return CommandType
     *
     * @throws \LogicException if no command is found
     */
    public function first(): array
    {
        $commands = $this->getCommands();
        $command = \reset($commands);

        return \is_array($command) ? $command : throw new \LogicException('No command found.');
    }

    /**
     * Gets the command for the given name.
     *
     * @phpstan-return CommandType
     *
     * @throws \InvalidArgumentException if the given command name is not found
     */
    public function getCommand(string $name): array
    {
        return $this->getCommands()[$name] ?? throw new \InvalidArgumentException(\sprintf('Unable to find the command "%s".', $name));
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
            fn (array $carry, array $command): array => $command['hidden']
                ? $carry
                : $carry + [$command['name'] => $this->parseCommand($command)],
            []
        );
        \ksort($commands);

        return $commands;
    }

    /**
     * @phpstan-param array<string, ArgumentSourceType> $arguments
     *
     * @phpstan-return array<string, InputType>
     */
    private function parseArguments(array $arguments): array
    {
        $result = [];
        foreach ($arguments as $key => $argument) {
            $display = $this->encodeDefaultValue($argument['default']);
            $required = $this->parseRequiredArgument($argument);
            $isArray = $argument['is_array'];
            $result[$key] = [
                'name' => $argument['name'],
                'shortcut' => '',
                'shortcutName' => '',
                'description' => $this->parseDescription($argument['description']),
                'isRequired' => $required,
                'isAcceptValue' => false,
                'isArray' => $isArray,
                'default' => $argument['default'],
                'display' => $display,
                'extra' => $this->parseExtra($required, $isArray, $display),
            ];
        }

        return $result;
    }

    /**
     * @phpstan-param CommandSourceType $command
     *
     * @phpstan-return CommandType
     */
    private function parseCommand(array $command): array
    {
        $command['arguments'] = $this->parseArguments($command['definition']['arguments']);
        $command['options'] = $this->parseOptions($command['definition']['options']);
        $command['help'] = $command['help'] === $command['description'] ? '' : $this->parseDescription($command['help']);
        $command['description'] = \rtrim($command['description'], '.') . '.';
        if ([] === $command['arguments']) {
            $command['usage'] = [\sprintf('%s [options]', $command['name'])];
        } else {
            $command['usage'] = StringUtils::pregReplaceAll(self::USAGE_REPLACE, $command['usage']);
        }
        unset($command['definition']);

        return $command;
    }

    private function parseDescription(string $help): string
    {
        if (!StringUtils::isString($help)) {
            return $help;
        }

        $help = StringUtils::pregReplaceAll(self::HREF_REPLACE, $help);
        $help = StringUtils::replace(self::HELP_REPLACE, $help);

        return $this->replaceConsole($help);
    }

    private function parseExtra(bool $required, bool $array, string $display): string
    {
        $values = [];
        if ($required) {
            $values[] = '<span class="text-danger">Required</span>';
        }
        if ('[]' === $display || $array) {
            $values[] = '<span class="text-secondary">Multiple values allowed</span>';
        } elseif ('' !== $display) {
            $values[] = \sprintf('<span class="text-warning-emphasis">Default: %s</span>', $display);
        }
        if ([] === $values) {
            return '';
        }

        return \sprintf('(%s)', \implode(', ', $values));
    }

    /**
     * @phpstan-param array<string, OptionSourceType> $options
     *
     * @phpstan-return array<string, InputType>
     */
    private function parseOptions(array $options): array
    {
        $result = [];
        foreach ($options as $key => $option) {
            $name = $option['name'];
            $display = $this->encodeDefaultValue($option['default']);
            $required = $this->parseRequiredOption($option);
            $shortcut = $option['shortcut'];
            $isArray = $option['is_multiple'];
            $result[$key] = [
                'name' => $name,
                'shortcut' => $shortcut,
                'shortcutName' => $this->parseShortcut($name, $shortcut),
                'description' => $this->parseDescription($option['description']),
                'isRequired' => $required,
                'isAcceptValue' => $option['accept_value'],
                'isArray' => $isArray,
                'default' => $option['default'],
                'display' => $display,
                'extra' => $this->parseExtra($required, $isArray, $display),
            ];
        }

        return $result;
    }

    /**
     * @phpstan-param ArgumentSourceType $argument
     */
    private function parseRequiredArgument(array $argument): bool
    {
        if (!$argument['is_required']) {
            return false;
        }

        if (null === $argument['default']) {
            return false;
        }

        return !($argument['is_array'] && \is_array($argument['default']));
    }

    /**
     * @phpstan-param OptionSourceType $option
     */
    private function parseRequiredOption(array $option): bool
    {
        if (!$option['is_value_required']) {
            return false;
        }

        if (null === $option['default']) {
            return false;
        }

        return !($option['is_multiple'] && \is_array($option['default']));
    }

    private function parseShortcut(string $name, string $shortcut): string
    {
        $format = '' === $shortcut ? '%4s%s' : '%s, %s';

        return \sprintf($format, $shortcut, $name);
    }

    private function replaceCarriageReturn(string $subject): string
    {
        return \str_replace("\r", '', $subject);
    }

    private function replaceConsole(string $subject): string
    {
        return \str_replace(self::CONSOLE_REPLACE, 'bin/console', $subject);
    }

    private function updateOutput(string $output): string
    {
        $output = $this->replaceConsole($output);
        $output = $this->replaceCarriageReturn($output);
        $lines = \array_map(\rtrim(...), \explode(StringUtils::NEW_LINE, \rtrim($output)));

        return \implode(StringUtils::NEW_LINE, $lines);
    }
}
