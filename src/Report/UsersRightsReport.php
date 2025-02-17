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
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Traits\ArrayTrait;
use App\Traits\RoleTranslatorTrait;
use Elao\Enum\FlagBag;
use fpdf\Enums\PdfMove;
use fpdf\PdfBorder;

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
    private readonly bool $superAdmin;

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
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
        /** @var User|string $key */
        $key = $event->getGroupKey();
        if (!$key instanceof User) {
            return false;
        }

        $position = $this->getPosition();
        $text = $key->getUserIdentifier();
        $description = $key->isEnabled() ? $this->translateRole($key) : $this->trans('common.value_disabled');
        $event->group->apply($this);
        $this->cell(border: PdfBorder::all());
        $this->setPosition($position);
        $width = $this->getStringWidth($text);
        $this->cell(width: $width, text: $text);
        PdfStyle::default()->apply($this);
        $this->cell(text: ' - ' . $description, move: PdfMove::NEW_LINE);

        return true;
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->bulletStyle = PdfStyle::getBulletStyle();
        $this->entityStyle = PdfStyle::getCellStyle()->setIndent(2);
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

    /**
     * @psalm-param FlagBag<EntityPermission> $rights
     */
    private function getRightText(FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights->hasFlags($permission) ? PdfStyle::BULLET : null;
    }

    /**
     * @psalm-param FlagBag<EntityPermission> $rights
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

        if ($entity instanceof User) {
            $this->addBookmark($entity->getUserIdentifier(), true, 1);
            $table->setGroupKey($entity);
        } else {
            $role = $this->translateRole($entity);
            $this->addBookmark($role, true, 1);
            $table->setGroupKey($role);
        }

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
