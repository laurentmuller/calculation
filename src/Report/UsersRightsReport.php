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
use App\Model\Role;
use App\Pdf\Events\PdfGroupEvent;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Traits\RoleTranslatorTrait;
use Elao\Enum\FlagBag;
use fpdf\PdfBorder;
use fpdf\PdfMove;
use fpdf\PdfTextAlignment;

/**
 * Report for the list of user rights.
 *
 * @extends AbstractArrayReport<User>
 */
class UsersRightsReport extends AbstractArrayReport implements PdfGroupListenerInterface
{
    use RoleTranslatorTrait;

    /**
     * The right cell style.
     */
    private ?PdfStyle $rightStyle = null;

    /**
     * The title cell style.
     */
    private ?PdfStyle $titleStyle = null;

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly RoleBuilderService $builder
    ) {
        parent::__construct($controller, $entities);
        $this->setTitleTrans('user.rights.title', [], true);
    }

    public function outputGroup(PdfGroupEvent $event): bool
    {
        /** @psalm-var Role|User|null $key */
        $key = $event->group->getKey();
        if ($key instanceof Role) {
            $text = $this->trans('user.fields.role') . ' ' . $this->translateRole($key);
            $event->table->singleLine($text, $event->group->getStyle());

            return true;
        }

        if ($key instanceof User) {
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

        return false;
    }

    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->rightStyle = PdfStyle::getBulletStyle();
        $this->titleStyle = PdfStyle::getCellStyle()->setIndent(2);
        $table = $this->createTableBuilder();
        $this->addBookmark($this->trans('user.roles.name'), true);
        $this->outputRoleAdmin($table);
        $this->outputRoleUser($table);
        $this->addBookmark($this->trans('user.list.title'));
        $this->outputUsers($entities, $table);
        $this->renderTotal($table, $entities);

        return true;
    }

    /**
     * Creates the right table builder.
     *
     * @return PdfGroupTable the table builder
     */
    private function createTableBuilder(): PdfGroupTable
    {
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getCellStyle()->setFontBold())
            ->setGroupListener($this)
            ->addColumn(PdfColumn::left($this->trans('user.rights.table_title'), 50));
        $permissions = EntityPermission::sorted();
        foreach ($permissions as $permission) {
            $table->addColumn(PdfColumn::center($this->trans($permission), 25, true));
        }
        $table->outputHeaders();

        return $table;
    }

    private function getApplication(): ApplicationService
    {
        return $this->controller->getApplication();
    }

    /**
     * Gets the cell text for the given rights and attribute.
     *
     * @psalm-param FlagBag<EntityPermission> $rights
     */
    private function getRightText(FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights->hasFlags($permission) ? PdfStyle::BULLET : null;
    }

    /**
     * Output rights.
     *
     * @psalm-param FlagBag<EntityPermission> $rights
     */
    private function outputRights(PdfGroupTable $table, EntityName $entity, FlagBag $rights): self
    {
        $table->startRow()
            ->add(text: $this->trans($entity), style: $this->titleStyle);
        foreach (EntityPermission::sorted() as $permission) {
            $table->add(text: $this->getRightText($rights, $permission), style: $this->rightStyle);
        }
        $table->endRow();

        return $this;
    }

    /**
     * Output rights for a role.
     */
    private function outputRole(PdfGroupTable $table, Role|User $entity): void
    {
        $outputUsers = $entity->isAdmin();
        $names = EntityName::sorted();
        $lines = \count($names) - 1;
        if (!$outputUsers) {
            --$lines;
        }
        if (!$this->isPrintable((float) $lines * self::LINE_HEIGHT)) {
            $this->addPage();
        }
        if ($entity instanceof User) {
            $this->addBookmark($entity->getUserIdentifier(), true, 1);
        } else {
            $this->addBookmark($this->translateRole($entity), level: 1);
        }
        $table->setGroupKey($entity);
        foreach ($names as $name) {
            if (EntityName::LOG === $name) {
                continue;
            }
            if ($outputUsers || EntityName::USER !== $name) {
                $rights = $entity->getPermission($name);
                $this->outputRights($table, $name, $rights);
            }
        }
    }

    /**
     * Output default rights for the administrator role.
     *
     * @param PdfGroupTable $table the builder to output to
     */
    private function outputRoleAdmin(PdfGroupTable $table): void
    {
        $this->outputRole($table, $this->getApplication()->getAdminRole());
    }

    /**
     * Output default rights for the user role.
     *
     * @param PdfGroupTable $table the builder to output to
     */
    private function outputRoleUser(PdfGroupTable $table): void
    {
        $this->outputRole($table, $this->getApplication()->getUserRole());
    }

    /**
     * Output rights for users.
     *
     * @param User[]        $users the users
     * @param PdfGroupTable $table the builder to output to
     */
    private function outputUsers(array $users, PdfGroupTable $table): void
    {
        if ([] === $users) {
            return;
        }
        foreach ($users as $user) {
            if (!$user->isOverwrite()) {
                $rights = $this->builder->getRole($user)->getRights();
                $user->setRights($rights);
            }
            $this->outputRole($table, $user);
        }
    }

    private function renderTotal(PdfTable $table, array $entities): void
    {
        $roles = $this->translateCount(2, 'counters.roles');
        $users = $this->translateCount($entities, 'counters.users');
        $text = \sprintf('%s, %s', $roles, $users);
        $table->singleLine($text, PdfStyle::getHeaderStyle(), PdfTextAlignment::LEFT);
    }
}
