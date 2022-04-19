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

namespace App\Faker;

/**
 * Extends Factory with custom generator.
 *
 * @author Laurent Muller
 */
class Factory extends \Faker\Factory
{
    /**
     * {@inheritDoc}
     */
    public static function create($locale = self::DEFAULT_LOCALE): Generator
    {
        $generator = new Generator();

        /** @psalm-var string $provider */
        foreach (static::$defaultProviders as $provider) {
            $providerClassName = self::getProviderClassname($provider, $locale);
            $generator->addProvider(new $providerClassName($generator));
        }

        return $generator;
    }
}
