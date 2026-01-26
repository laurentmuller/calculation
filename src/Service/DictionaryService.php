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

/**
 * Service to get random words.
 */
class DictionaryService
{
    private const array DICTIONARY = [
        'ACCOINTANCE',
        'AMPHIGOURI',
        'ANONCHALIR',
        'BARBITURIQUE',
        'BELLUAIRE',
        'BINAURAL',
        'BROUILLAMINI',
        'CACOCHYME',
        'CALIGINEUX',
        'CAUTELEUX',
        'CLOPINETTE',
        'COQUECIGRUE',
        'COSMOGONIE',
        'CRAPOUSSIN',
        'DAMASQUINER',
        'DIFFICULTUEUX',
        'DIPSOMANIE',
        'DODELINER',
        'ENGOULEVENT',
        'ERGASTULE',
        'ESCARPOLETTE',
        'ESSORILLER',
        'FALARIQUE',
        'FLAVESCENT',
        'FORLANCER',
        'GALIMATIAS',
        'GNOGNOTTE',
        'GRACILE',
        'HALIEUTIQUE',
        'HARMATTAN',
        'HYPERGAMIE',
        'HYPNAGOGIQUE',
        'ILLUMINISME',
        'IMMARCESCIBLE',
        'IMPAVIDE',
        'INCARNADIN',
        'JACTANCE',
        'JANOTISME',
        'LEPTOSOME',
        'LUSTRINE',
        'MARGOULIN',
        'MIGNARDISE',
        'MYOCLONIE',
        'NONOBSTANT',
        'NITESCENCE',
        'OBOMBRER',
        'OBJURGATION',
        'ODALISQUE',
        'PALINODIE',
        'PANOPTIQUE',
        'PARANGON',
        'PETRICHOR',
        'PUSILLANIME',
        'RATIOCINER',
        'RUBIGINEUX',
        'SMARAGDIN',
        'SOLIFLORE',
        'SOPHISME',
        'STOCHASTIQUE',
        'THAUMATURGE',
        'TRUCHEMENT',
        'VERGOGNE',
        'VERTUGADIN',
        'ZINZINULER',
    ];

    /**
     * Gets a random word.
     */
    public function getRandomWord(): string
    {
        return self::DICTIONARY[\array_rand(self::DICTIONARY)];
    }
}
