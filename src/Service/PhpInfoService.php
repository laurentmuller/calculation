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

use App\Utils\StringUtils;
use STS\Phpinfo\Info;
use STS\Phpinfo\Models\Config;
use STS\Phpinfo\Models\Group;
use STS\Phpinfo\Models\Module;
use STS\Phpinfo\PhpInfo;

/**
 * Service to get PHP information.
 *
 * @phpstan-type EntryType = array{
 *     value: string,
 *     color: bool,
 *     no_value: bool,
 *     redacted: bool,
 *     enabled: bool,
 *     disabled: bool
 * }
 * @phpstan-type ConfigType = array{
 *     name: string,
 *     local: EntryType,
 *     master: EntryType|null
 * }
 * @phpstan-type GroupType = array{
 *     name: string|null,
 *     note: string|null,
 *     headings: bool,
 *     configs: ConfigType[]
 * }
 * @phpstan-type ModuleType = array{
 *     name: string,
 *     groups: GroupType[]
 * }
 * @phpstan-type InfoType = array{
 *     version: string,
 *     hostname: string|null,
 *     os: string|null,
 *     modules: ModuleType[]
 * }
 */
class PhpInfoService
{
    public const string COLUMN_DIRECTIVE = 'Directive';
    public const string COLUMN_LOCAL = 'Local Value';
    public const string COLUMN_MASTER = 'Master Value';

    private const array DISABLED = ['false', 'off', 'no', 'disabled', 'not enabled'];
    private const array ENABLED = ['true', 'on', 'yes', 'enabled', 'supported', 'active'];

    /**
     * @return InfoType
     */
    public function getPhpInfo(): array
    {
        $info = Info::capture();

        return [
            'version' => $info->version(),
            'hostname' => $info->hostname(),
            'os' => $info->os(),
            'modules' => $this->parseModules($info),
        ];
    }

    private function convertValue(string $value): string
    {
        if ('(none)' === $value) {
            return 'None';
        }
        if ('UTF-8' === \mb_detect_encoding($value, \mb_detect_order(), true)) {
            $value = \mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            $value = \str_replace(['✘ ', '✔ ', '⊕'], '', $value);
        }

        return $value;
    }

    private function isDisabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::DISABLED, true);
    }

    private function isEnabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::ENABLED, true);
    }

    private function isNoValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase('no value', $value);
    }

    private function isRedacted(string $name): bool
    {
        $keys = ['_KEY', '_USER_NAME', 'APP_SECRET', '_PASSWORD', 'MAILER_DSN', 'DATABASE_URL'];
        foreach ($keys as $key) {
            if (StringUtils::containsIgnoreCase($name, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ConfigType
     */
    private function parseConfig(Config $config): array
    {
        $name = $config->name();
        $localValue = $this->convertValue($config->localValue() ?? 'No Value');
        $masterValue = $this->convertValue($config->masterValue() ?? 'No Value');
        $localConfig = $this->parseValue($name, $localValue);
        $masterConfig = $config->hasMasterValue() && '' !== $masterValue ? $this->parseValue($name, $masterValue) : null;

        return [
            'name' => $name,
            'local' => $localConfig,
            'master' => $masterConfig,
        ];
    }

    /**
     * @return ConfigType[]
     */
    private function parseConfigs(Group $group): array
    {
        return \array_map(
            fn (Config $config): array => $this->parseConfig($config),
            $group->configs()->toArray()
        );
    }

    /**
     * @return GroupType
     */
    private function parseGroup(Group $group): array
    {
        return [
            'name' => $group->name(),
            'note' => StringUtils::trim($group->note()),
            'headings' => $this->parseHeadings($group),
            'configs' => $this->parseConfigs($group),
        ];
    }

    /**
     * @return GroupType[]
     */
    private function parseGroups(Module $module): array
    {
        return \array_map(
            fn (Group $group): array => $this->parseGroup($group),
            $module->groups()->toArray()
        );
    }

    private function parseHeadings(Group $group): bool
    {
        if ($group->hasHeadings()) {
            return null !== \array_find(
                $group->configs()->toArray(),
                static fn (Config $config): bool => $config->hasMasterValue()
            );
        }

        return false;
    }

    /**
     * @return ModuleType
     */
    private function parseLoadedExtensions(): array
    {
        /** @phpstan-var EntryType $local */
        $local = [
            'value' => \implode(', ', \get_loaded_extensions()),
            'color' => false,
            'no_value' => false,
            'redacted' => false,
            'enabled' => false,
            'disabled' => false,
        ];

        /** @phpstan-var ConfigType $config */
        $config = [
            'name' => 'Loaded Extensions',
            'local' => $local,
            'master' => null,
        ];
        /** @phpstan-var GroupType $group */
        $group = [
            'name' => null,
            'note' => null,
            'headings' => false,
            'configs' => [$config],
        ];

        return [
            'name' => 'Configuration',
            'groups' => [$group],
        ];
    }

    /**
     * @return ModuleType
     */
    private function parseModule(Module $module): array
    {
        return [
            'name' => $module->name(),
            'groups' => $this->parseGroups($module),
        ];
    }

    /**
     * @return ModuleType[]
     */
    private function parseModules(PhpInfo $info): array
    {
        $modules = \array_map(
            fn (Module $module): array => $this->parseModule($module),
            $info->modules()->toArray()
        );

        // add the loaded extensions
        $offset = \max(0, \count($modules) - 2);
        $module = $this->parseLoadedExtensions();
        \array_splice($modules, $offset, 0, [$module]);

        return $modules;
    }

    /**
     * @return EntryType
     */
    private function parseValue(string $name, string $value): array
    {
        $color = false;
        if (StringUtils::pregMatch('/#[\\da-f]{6}/i', $value, $matches)) {
            $value = $matches[0];
            $color = true;
        }
        $redacted = false;
        if ($this->isRedacted($name)) {
            $value = '********';
            $redacted = true;
        }
        $no_value = false;
        if ($this->isNoValue($value)) {
            $value = 'No value';
            $no_value = true;
        }
        $enabled = false;
        if ($this->isEnabledValue($value)) {
            $value = StringUtils::capitalize($value);
            $enabled = true;
        }
        $disabled = false;
        if ($this->isDisabledValue($value)) {
            $value = StringUtils::capitalize($value);
            $disabled = true;
        }

        return [
            'value' => $value,
            'color' => $color,
            'no_value' => $no_value,
            'redacted' => $redacted,
            'enabled' => $enabled,
            'disabled' => $disabled,
        ];
    }
}
