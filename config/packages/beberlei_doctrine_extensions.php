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

use DoctrineExtensions\Query\Mysql as DbFunction;
use Symfony\Config\DoctrineConfig;

return static function (DoctrineConfig $config): void {
    $dql = $config->orm()
        ->entityManager('default')
        ->dql();

    $dql->datetimeFunction('date_format', DbFunction\DateFormat::class)
        ->datetimeFunction('day', DbFunction\Day::class)
        ->datetimeFunction('month', DbFunction\Month::class)
        ->datetimeFunction('week', DbFunction\Week::class)
        ->datetimeFunction('year', DbFunction\Year::class);

    $dql->numericFunction('round', DbFunction\Round::class);

    $dql->stringFunction('ifelse', DbFunction\IfElse::class)
        ->stringFunction('ifnull', DbFunction\IfNull::class);
};
