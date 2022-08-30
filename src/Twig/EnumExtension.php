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

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for enumeration types.
 */
class EnumExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('enum', $this->enum(...)),
        ];
    }

    private function enum(string $fullClassName): object
    {
        $parts = \explode('::', $fullClassName);
        /** @psalm-var class-string<\UnitEnum> $className */
        $className = $parts[0];
        $enumName = $parts[1] ?? null;

        if (!\enum_exists($className)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not an enum.', $className));
        }

        if (null !== $enumName) {
            return (object) \constant($fullClassName);
        }

        return new class($fullClassName) {
            public function __construct(private readonly string $fullClassName)
            {
            }

            public function __call(string $caseName, array $arguments): mixed
            {
                // @phpstan-ignore-next-line
                return \call_user_func_array([$this->fullClassName, $caseName], $arguments);
            }
        };
    }
}
