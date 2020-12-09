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

use App\Entity\CalculationCategory;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\Event\PostSubmitEvent;

/**
 * Calculation category edit type.
 *
 * @author Laurent Muller
 */
class CalculationCategoryType extends AbstractEntityType
{
    /**
     * @var CategoryRepository
     */
    private $repository;

    /**
     * Constructor.
     *
     * @param CategoryRepository $repository the repository to update the entity
     */
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct(CalculationCategory::class);
        $this->repository = $repository;
    }

    /**
     * Handles the post submit event.
     */
    public function onPostSubmit(PostSubmitEvent $event): void
    {
        // get values
        $form = $event->getForm();

        /** @var CalculationCategory $data */
        $data = $form->getData();

        // update category if needed
        if (null === $data->getCategory() && $form->has('categoryId')) {
            $id = (int) $form->get('categoryId')->getData();
            $category = $this->repository->find($id);
            $data->setCategory($category);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // default
        $helper->field('categoryId')->addHiddenType();
        $helper->field('code')->addHiddenType();

        // items
        $helper->field('items')
            ->updateOption('prototype_name', '__itemIndex__')
            ->addCollectionType(CalculationItemType::class);

        // add event
        $helper->addPostSubmitListener([$this, 'onPostSubmit']);
    }
}
