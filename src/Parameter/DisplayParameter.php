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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Enums\EntityAction;
use App\Enums\TableView;

/**
 * Display parameter.
 */
class DisplayParameter implements ParameterInterface
{
    #[Parameter('display_mode', TableView::TABLE)]
    private TableView $displayMode = TableView::TABLE;

    #[Parameter('edit_action', EntityAction::EDIT)]
    private EntityAction $editAction = EntityAction::EDIT;

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_display';
    }

    public function getDisplayMode(): TableView
    {
        return $this->displayMode;
    }

    public function getEditAction(): EntityAction
    {
        return $this->editAction;
    }

    public function isActionEdit(): bool
    {
        return EntityAction::EDIT === $this->editAction;
    }

    public function isActionNone(): bool
    {
        return EntityAction::NONE === $this->editAction;
    }

    public function isActionShow(): bool
    {
        return EntityAction::SHOW === $this->editAction;
    }

    public function setDisplayMode(TableView $displayMode): self
    {
        $this->displayMode = $displayMode;

        return $this;
    }

    public function setEditAction(EntityAction $editAction): self
    {
        $this->editAction = $editAction;

        return $this;
    }
}
