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

use App\Traits\EnablementValueTrait;
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
 *     headers: string[]|null,
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
    use EnablementValueTrait;

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

    /**
     * @param ModuleType[] $modules
     */
    private function addExtensionsModule(array &$modules): void
    {
        $local = [
            'value' => \implode(', ', \get_loaded_extensions()),
            'color' => false,
            'no_value' => false,
            'redacted' => false,
            'enabled' => false,
            'disabled' => false,
        ];

        $config = [
            'name' => 'Loaded Extensions',
            'local' => $local,
            'master' => null,
        ];
        $group = [
            'name' => null,
            'note' => null,
            'headings' => false,
            'configs' => [$config],
            'headers' => null,
        ];

        $module = [
            'name' => 'Configuration',
            'groups' => [$group],
        ];

        $offset = \max(0, \count($modules) - 2);
        \array_splice($modules, $offset, 0, [$module]);
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
        if (\str_starts_with($value, '(') && \str_ends_with($value, ')')) {
            return \ucfirst(\trim($value, '()'));
        }

        return $value;
    }

    private function isNoValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase('no value', $value);
    }

    private function isRedacted(string $name): bool
    {
        $keys = ['_KEY', '_USER_NAME', 'APP_SECRET', '_PASSWORD', 'MAILER_DSN', 'DATABASE_URL', '_SESSION_ID'];
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
            'headers' => $this->parseHeaders($group),
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

    /**
     * @return string[]|null
     */
    private function parseHeaders(Group $group): ?array
    {
        return $group->hasHeadings() ? $group->headings()->toArray() : null;
    }

    /**
     * @return ModuleType
     */
    private function parseModule(Module $module): array
    {
        $module = [
            'name' => $module->name(),
            'groups' => $this->parseGroups($module),
        ];
        if ('PHP Variables' === $module['name']) {
            return $this->parseVariables($module);
        }

        return $module;
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

        // add loaded extensions
        $this->addExtensionsModule($modules);

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

    /**
     * @param ModuleType $module
     *
     * @return ModuleType
     */
    private function parseVariables(array $module): array
    {
        if (1 !== \count($module['groups'])) {
            return $module;
        }

        $groups = [];
        $group = $module['groups'][0];
        $pattern = '/\\$_(.*)\\[\'(.*)\']/';

        foreach ($group['configs'] as $config) {
            if (!StringUtils::pregMatch($pattern, $config['name'], $matches)) {
                continue;
            }
            $name = $matches[1];
            $groups[$name] ??= [
                'name' => $name,
                'note' => null,
                'headers' => $group['headers'],
                'configs' => [],
            ];
            $groups[$name]['configs'][] = [
                'name' => $matches[2],
                'local' => $config['local'],
                'master' => $config['master'],
            ];
        }
        $module['groups'] = $groups;

        return $module;
    }
}
