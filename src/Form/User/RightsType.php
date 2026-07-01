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

namespace App\Form\User;

use App\Enums\EntityPermission;
use App\Service\EntityNameService;
use Elao\Enum\FlagBag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The access permissions type.
 *
 * @extends AbstractType<EntityPermission[]>
 */
class RightsType extends AbstractType implements DataMapperInterface
{
    public function __construct(private readonly EntityNameService $service)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entities = $this->service->getEntities();
        foreach ($entities as $entity) {
            $builder->add(
                $entity->getFormField(),
                EntityPermissionType::class,
                ['label' => $entity]
            );
        }
        $builder->setDataMapper($this);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('empty_data', 0);
    }

    /**
     * @param \Traversable<FormInterface<mixed>> $forms
     */
    #[\Override]
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (null === $viewData) {
            return;
        }
        if (!\is_numeric($viewData)) {
            throw new UnexpectedTypeException($viewData, 'numeric');
        }

        $viewData = (int) $viewData;
        /** @var FormInterface<mixed>[] $forms */
        $forms = \iterator_to_array($forms);

        $entities = $this->service->getEntities();
        foreach ($entities as $entity) {
            $value = $entity->getOffsetValue($viewData);
            $flagBag = new FlagBag(EntityPermission::class, $value);
            $forms[$entity->getFormField()]->setData($flagBag);
        }
    }

    /**
     * @param \Traversable<FormInterface<mixed>> $forms
     */
    #[\Override]
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $viewData = 0;
        /** @var FormInterface<mixed>[] $forms */
        $forms = \iterator_to_array($forms);

        $entites = $this->service->getEntities();
        foreach ($entites as $entity) {
            $form = $forms[$entity->getFormField()];
            /** @var FlagBag<EntityPermission> $flagBag */
            $flagBag = $form->getData();
            $viewData |= $entity->getShiftedValue($flagBag->getValue());
        }
    }
}
