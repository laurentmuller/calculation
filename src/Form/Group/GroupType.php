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

namespace App\Form\Group;

use App\Entity\AbstractEntity;
use App\Entity\Group;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;

/**
 * Group edit type.
 *
 * @template-extends AbstractEntityType<Group>
 */
class GroupType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Group::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('code')
            ->maxLength(30)
            ->addTextType();

        $helper->field('description')
            ->notRequired()
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->addTextareaType();

        $helper->field('margins')
            ->addCollectionType(GroupMarginType::class);
    }
}
