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

use App\Entity\Group;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Interfaces\EntityInterface;

/**
 * Group edit type.
 *
 * @template-extends AbstractEntityType<Group>
 */
class GroupType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(Group::class);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('code')
            ->maxLength(EntityInterface::MAX_CODE_LENGTH)
            ->widgetClass('uc-first')
            ->addTextType();

        $helper->field('description')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->widgetClass('uc-first')
            ->notRequired()
            ->addTextareaType();

        $helper->field('margins')
            ->addCollectionType(GroupMarginType::class);
    }
}
