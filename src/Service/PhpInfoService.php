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
 * Service get PHP information.
 *
 * @phpstan-type ValueEntryType = array{
 *     value: string,
 *     color: bool,
 *     no_value: bool,
 *     redacted: bool,
 *     enabled: bool,
 *     disabled: bool
 * }
 * @phpstan-type ConfigType = array{
 *     name: string,
 *     local: ValueEntryType,
 *     master: ValueEntryType|null
 * }
 * @phpstan-type GroupType = array{
 *     name: string|null,
 *     note: string|null,
 *     headings: string[]|null,
 *     configs: ConfigType[]
 * }
 * @phpstan-type ModuleType = array{
 *     name: string,
 *     groups: GroupType[]
 * }
 * @phpstan-type PhpInfoType = array{
 *     version: string,
 *     hostname: string|null,
 *     os: string|null,
 *     modules: ModuleType[]
 * }
 */
class PhpInfoService
{
    private const array DISABLED = ['off', 'no', 'disabled', 'not enabled'];
    private const array ENABLED = ['active', 'on', 'yes', 'enabled', 'supported'];
    private const string REDACTED = '********';

    /**
     * @return PhpInfoType
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

    private function isColorValue(string $value): bool
    {
        return StringUtils::pregMatch('/^#[\\da-f]{6}$/i', $value);
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

    /**
     * @return ConfigType
     */
    private function parseConfig(Config $config): array
    {
        $name = $config->name();
        $localValue = $config->localValue() ?? 'No value';
        $masterValue = $config->masterValue() ?? 'No value';
        $localConfig = $this->parseValue($name, $localValue);
        $masterConfig = $config->hasMasterValue() ? $this->parseValue($name, $masterValue) : null;

        return [
            'name' => $name,
            'local' => $localConfig,
            'master' => $masterConfig,
        ];
    }

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
            'note' => $group->note(),
            'headings' => $this->parseHeadings($group),
            'configs' => $this->parseConfigs($group),
        ];
    }

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
    private function parseHeadings(Group $group): ?array
    {
        if (!$group->hasHeadings()) {
            return null;
        }

        return \array_map(static fn (mixed $heading): string => (string) $heading, $group->headings()->toArray());
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

    private function parseModules(PhpInfo $info): array
    {
        return \array_map(
            fn (Module $module): array => $this->parseModule($module),
            $info->modules()->toArray()
        );
    }

    /**
     * @return ValueEntryType
     */
    private function parseValue(string $name, string $value): array
    {
        $value = \htmlspecialchars_decode($value);

        $redacted = false;
        $keys = ['_KEY', '_USER_NAME', 'APP_SECRET', '_PASSWORD', 'MAILER_DSN', 'DATABASE_URL'];
        foreach ($keys as $key) {
            if (\str_contains($name, $key)) {
                $value = self::REDACTED;
                $redacted = true;
                break;
            }
        }

        if ('(none)' === $value) {
            $value = 'None';
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
            'color' => $this->isColorValue($value),
            'no_value' => $no_value,
            'redacted' => $redacted,
            'enabled' => $enabled,
            'disabled' => $disabled,
        ];
    }
}
