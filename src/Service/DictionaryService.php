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
    private const DICTIONNARY = [
        'Accointance',
        'Amphigouri',
        'Anonchalir',
        'Barbiturique',
        'Belluaire',
        'Binaural',
        'Brouillamini',
        'Cacochyme',
        'Caligineux',
        'Cauteleux',
        'Clopinette',
        'Coquecigrue',
        'Cosmogonie',
        'Crapoussin',
        'Damasquiner',
        'Difficultueux',
        'Dipsomanie',
        'Dodeliner',
        'Engoulevent',
        'Ergastule',
        'Escarpolette',
        'Essoriller',
        'Falarique',
        'Flavescent',
        'Forlancer',
        'Galimatias',
        'Gnognotte',
        'Gracile',
        'Halieutique',
        'Harmattan',
        'Hypergamie',
        'Hypnagogique',
        'Illuminisme',
        'Immarcescible',
        'Impavide',
        'Incarnadin',
        'Jactance',
        'Janotisme',
        'Leptosome',
        'Lustrine',
        'Margoulin',
        'Mignardise',
        'Myoclonie',
        'Nonobstant',
        'Nitescence',
        'Obombrer',
        'Objurgation',
        'Odalisque',
        'Palinodie',
        'Panoptique',
        'Parangon',
        'Petrichor',
        'Pusillanime',
        'Ratiociner',
        'Rubigineux',
        'Smaragdin',
        'Soliflore',
        'Sophisme',
        'Stochastique',
        'Thaumaturge',
        'Truchement',
        'Vergogne',
        'Vertugadin',
        'Zinzinuler',
    ];

    /**
     * Gets a random word.
     */
    public function getRandomWord(): string
    {
        $dictionnary = $this->getWords();

        return \strtoupper($dictionnary[\array_rand($dictionnary)]);
    }

    /**
     * Gets words.
     *
     * @return string[]
     */
    public function getWords(): array
    {
        return self::DICTIONNARY;
    }
}
