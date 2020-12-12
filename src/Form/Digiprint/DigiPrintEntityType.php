<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Digiprint;

use App\Entity\DigiPrint;
use App\Repository\DigiPrintRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of DigiPrint.
 *
 * @author Laurent Muller
 */
class DigiPrintEntityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => DigiPrint::class,
            'choice_label' => 'display',
            'placeholder' => false,
            'query_builder' => function (DigiPrintRepository $r) {
                return $r->getSortedBuilder();
            },
            'choice_attr' => function (DigiPrint $choice) {
                return [
                    'data-prices' => \json_encode($choice->hasPrices()),
                    'data-backlits' => \json_encode($choice->hasBacklits()),
                    'data-replicatings' => \json_encode($choice->hasReplicatings()),
                ];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return EntityType::class;
    }
}
