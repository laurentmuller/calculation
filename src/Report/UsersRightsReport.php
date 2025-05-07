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

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Pdf\Events\PdfGroupEvent;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\ApplicationService;
use App\Service\FontAwesomeService;
use App\Service\RoleBuilderService;
use App\Traits\ArrayTrait;
use App\Traits\RoleTranslatorTrait;
use Elao\Enum\FlagBag;
use fpdf\Enums\PdfMove;
use fpdf\PdfBorder;
use fpdf\PdfRectangle;

/**
 * Report for the list of role and user rights.
 *
 * @extends AbstractArrayReport<User>
 */
class UsersRightsReport extends AbstractArrayReport implements PdfGroupListenerInterface
{
    use ArrayTrait;
    use RoleTranslatorTrait;

    private readonly ApplicationService $applicationService;
    private ?PdfStyle $bulletStyle = null;
    private ?PdfStyle $entityStyle = null;
    private ?PdfStyle $italicStyle = null;
    private readonly bool $superAdmin;

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly FontAwesomeService $fontAwesomeService,
        private readonly RoleBuilderService $roleBuilderService
    ) {
        parent::__construct($controller, $entities);
        $this->setTitleTrans('user.rights.title', [], true)
            ->setDescriptionTrans('user.rights.description');
        $this->applicationService = $controller->getApplicationService();
        $this->superAdmin = $this->anyMatch($entities, static fn (User $user): bool => $user->isSuperAdmin());
    }

    #[\Override]
    public function drawGroup(PdfGroupEvent $event): bool
    {
        /** @var User|Role $key */
        $key = $event->getGroupKey();
        if ($key instanceof Role) {
            return $this->drawGroupRole($event, $key);
        }

        return $this->drawGroupUser($event, $key);
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->bulletStyle = PdfStyle::getBulletStyle();
        $this->entityStyle = PdfStyle::getCellStyle()->setIndent(2);
        $this->italicStyle = PdfStyle::default()->setFontItalic();
        $table = $this->createTable();
        $this->outputRoles($table);
        $this->outputUsers($entities, $table);
        $this->outputTotal($table, $entities);

        return true;
    }

    private function createTable(): PdfGroupTable
    {
        $table = PdfGroupTable::instance($this)
            ->addColumn($this->leftColumn('user.rights.table_title', 50))
            ->setGroupListener($this);

        $permissions = EntityPermission::sorted();
        foreach ($permissions as $permission) {
            $table->addColumn($this->centerColumn($permission, 25, true));
        }
        $table->outputHeaders();

        return $table;
    }

    private function drawGroupRole(PdfGroupEvent $event, Role $role): true
    {
        /** @var positive-int $cols */
        $cols = $event->table->getColumnsCount();
        $cell = $this->getRoleCell(
            $role,
            $cols,
            $event->group->getStyle()
        );
        $event->table
            ->startRow()
            ->addCell($cell)
            ->endRow();

        return true;
    }

    private function drawGroupUser(PdfGroupEvent $event, User $user): true
    {
        // border
        $position = $this->getPosition();
        $this->cell(border: PdfBorder::all());
        $this->setPosition($position);

        // bounds
        $bounds = new PdfRectangle(
            $position->x,
            $position->y,
            $this->getPrintableWidth(),
            self::LINE_HEIGHT
        );

        // user identifier
        $cell = $this->getRoleCell($user, 1, $event->group->getStyle());
        $cell->output(
            parent: $this,
            bounds: $bounds
        );

        // move right
        $bounds->indent($cell->computeWidth($this) - $this->cellMargin);

        // description
        $cell = new PdfCell(
            text: '(' . $this->getUserDescription($user) . ')',
            style: $this->italicStyle
        );
        $cell->output(
            parent: $this,
            bounds: $bounds,
            move: PdfMove::NEW_LINE
        );

        return true;
    }

    /**
     * @return EntityName[]
     */
    private function getEntityNames(RoleInterface $role): array
    {
        $names = $this->removeValue(EntityName::sorted(), EntityName::LOG);
        if (!$role->isAdmin()) {
            $names = $this->removeValue($names, EntityName::USER);
        }
        if (!$this->applicationService->isDebug()) {
            $names = $this->removeValue($names, EntityName::CUSTOMER);
        }

        return $names;
    }

    private function getEntityText(Role|User $entity): string
    {
        return $entity instanceof User ? $entity->getUserIdentifier() : $this->translateRole($entity);
    }

    /**
     * @phpstan-param FlagBag<EntityPermission> $rights
     */
    private function getRightText(FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights->hasFlags($permission) ? PdfStyle::BULLET : null;
    }

    /**
     * @param positive-int $cols
     */
    private function getRoleCell(
        Role|User $entity,
        int $cols,
        ?PdfStyle $style = null
    ): PdfCell {
        $icon = $this->getRoleIcon($entity);
        $text = $this->getEntityText($entity);
        $cell = $this->fontAwesomeService->getFontAwesomeCell(
            icon: $icon,
            text: $text,
            cols: $cols,
            style: $style
        );

        return $cell ?? new PdfCell(text: $text, cols: $cols, style: $style);
    }

    private function getUserDescription(User $user): string
    {
        return $user->isEnabled() ? $this->translateRole($user) : $this->trans('common.value_disabled');
    }

    /**
     * @phpstan-param FlagBag<EntityPermission> $rights
     */
    private function outputRights(PdfGroupTable $table, EntityName $entity, FlagBag $rights): self
    {
        $table->startRow()
            ->add(text: $this->trans($entity), style: $this->entityStyle);
        foreach (EntityPermission::sorted() as $permission) {
            $table->add(text: $this->getRightText($rights, $permission), style: $this->bulletStyle);
        }
        $table->endRow();

        return $this;
    }

    private function outputRole(PdfGroupTable $table, Role|User $entity): void
    {
        $names = $this->getEntityNames($entity);
        if (!$this->isPrintable((float) \count($names) * self::LINE_HEIGHT)) {
            $this->addPage();
        }

        $text = $this->getEntityText($entity);
        $this->addBookmark($text, true, 1);
        $table->setGroupKey($entity);

        foreach ($names as $name) {
            $rights = $entity->getPermission($name);
            $this->outputRights($table, $name, $rights);
        }
    }

    private function outputRoles(PdfGroupTable $table): void
    {
        $this->addBookmark($this->trans('user.roles.name'), true);
        if ($this->superAdmin) {
            $this->outputRole($table, $this->roleBuilderService->getRoleSuperAdmin());
        }
        $this->outputRole($table, $this->applicationService->getAdminRole());
        $this->outputRole($table, $this->applicationService->getUserRole());
    }

    private function outputTotal(PdfTable $table, array $entities): void
    {
        $count = $this->superAdmin ? 3 : 2;
        $roles = $this->translateCount($count, 'counters.roles');
        $users = $this->translateCount($entities, 'counters.users');
        $table->singleLine(\sprintf('%s, %s', $roles, $users), PdfStyle::getHeaderStyle());
    }

    /**
     * @param User[] $users
     */
    private function outputUsers(array $users, PdfGroupTable $table): void
    {
        $this->addBookmark($this->trans('user.list.title'));
        foreach ($users as $user) {
            if (!$user->isOverwrite()) {
                $rights = $this->roleBuilderService->getRole($user)->getRights();
                $user->setRights($rights);
            }
            $this->outputRole($table, $user);
        }
    }
}
