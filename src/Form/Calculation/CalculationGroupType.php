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

namespace App\Form\Calculation;

use App\Entity\CalculationGroup;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Repository\GroupRepository;
use Symfony\Component\Form\Event\PostSubmitEvent;

/**
 * Calculation group edit type.
 *
 * @author Laurent Muller
 */
class CalculationGroupType extends AbstractEntityType
{
    /**
     * @var GroupRepository
     */
    private $repository;

    /**
     * Constructor.
     *
     * @param GroupRepository $repository the repository to update the entity
     */
    public function __construct(GroupRepository $repository)
    {
        parent::__construct(CalculationGroup::class);
        $this->repository = $repository;
    }

    /**
     * Handles the post submit event.
     */
    public function onPostSubmit(PostSubmitEvent $event): void
    {
        // get values
        $form = $event->getForm();

        /** @var CalculationGroup $data */
        $data = $form->getData();

        // update group if needed
        if (null === $data->getGroup() && $form->has('groupId')) {
            $id = (int) $form->get('groupId')->getData();
            $group = $this->repository->find($id);
            $data->setGroup($group);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // default
        $helper->field('groupId')->addHiddenType();
        $helper->field('code')->addHiddenType();

        // items
        $helper->field('categories')
            ->updateOption('prototype_name', '__groupIndex__')
            ->addCollectionType(CalculationCategoryType::class);

        // add event
        $helper->addPostSubmitListener([$this, 'onPostSubmit']);
    }
}
