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

namespace App\Form\Type;

use App\Model\MonthRange;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<MonthRange>
 */
class MonthRangeType extends AbstractType
{
    private const string FROM_FORMAT = 'Y-m-01';
    private const string TO_FORMAT = 'Y-m-t';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var DatePoint $minDate */
        $minDate = $options['min_date'];
        /** @var DatePoint $maxDate */
        $maxDate = $options['max_date'];

        $startChoices = [];
        $endChoices = [];
        $current = $minDate->modify('first day of this month 00:00:00');
        $endLimit = $maxDate->modify('first day of this month 00:00:00');

        while ($current <= $endLimit) {
            $label = self::formatDate($current);
            $startChoices[$label] = $current->format(self::FROM_FORMAT);
            $endChoices[$label] = $current->format(self::TO_FORMAT);
            $current = $current->modify('+1 month');
        }

        $builder
            ->add('from', ChoiceType::class, [
                'label' => $options['from_label'],
                'required' => $options['required'],
                'choices' => \array_reverse($startChoices),
            ])
            ->add('to', ChoiceType::class, [
                'label' => $options['to_label'],
                'required' => $options['required'],
                'choices' => \array_reverse($endChoices),
            ]);

        $builder->get('from')->addModelTransformer(new CallbackTransformer(
            fn (DatePoint $value): string => $this->toString($value, self::FROM_FORMAT),
            fn (string $value): DatePoint => $this->toDatePoint($value, '00:00:00')
        ));

        $builder->get('to')->addModelTransformer(new CallbackTransformer(
            fn (DatePoint $value): string => $this->toString($value, self::TO_FORMAT),
            fn (string $value): DatePoint => $this->toDatePoint($value, '23:59:59')
        ));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['min_date', 'max_date'])
            ->setDefaults([
                'data_class' => MonthRange::class,
                'from_label' => 'From',
                'to_label' => 'To',
            ])
            ->setAllowedTypes('min_date', DatePoint::class)
            ->setAllowedTypes('max_date', DatePoint::class)
            ->setAllowedTypes('from_label', ['null', 'string'])
            ->setAllowedTypes('to_label', ['null', 'string']);
    }

    public static function formatDate(DatePoint $date): string
    {
        return FormatUtils::formatDate($date, pattern: 'MMMM yyyy');
    }

    private function toDatePoint(string $value, string $suffix): DatePoint
    {
        return new DatePoint($value . ' ' . $suffix);
    }

    private function toString(DatePoint $value, string $format): string
    {
        return $value->format($format);
    }
}
