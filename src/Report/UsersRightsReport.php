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
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Traits\RoleTranslatorTrait;
use Elao\Enum\FlagBag;

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
    }

    public function outputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        /** @var Role|User|null $key */
        $key = $group->getKey();
        if ($key instanceof Role) {
            $description = $this->trans('user.fields.role') . ' ';
            $description .= $this->translateRole($key);
            $parent->singleLine($description, $group->getStyle());

            return true;
        }

        if ($key instanceof User) {
            $text = $key->getUserIdentifier();
            $description = $key->isEnabled() ? $this->translateRole($key) : $this->trans('common.value_disabled');
            [$x, $y] = $this->GetXY();
            $group->apply($this);
            $this->Cell(border: PdfBorder::all());
            $this->SetXY($x, $y);
            $width = $this->GetStringWidth($text);
            $this->Cell(w: $width, txt: $text);
            PdfStyle::default()->setFontItalic()->apply($this);
            $this->Cell(txt: ' - ' . $description, ln: PdfMove::NEW_LINE);

            return true;
        }

        return false;
    }

    /**
     * @throws PdfException
     */
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('user.rights.title', [], true);

        $this->AddPage();
        $this->rightStyle = PdfStyle::getBulletStyle();
        $this->titleStyle = PdfStyle::getCellStyle()->setIndent(2);
        $table = $this->createTableBuilder();
        $this->outputRoleAdmin($table);
        $this->outputRoleUser($table);
        $this->outputUsers($entities, $table);
        $this->renderTotal($table, $entities);

        return true;
    }

    /**
     * Creates the right table builder.
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function createTableBuilder(): PdfGroupTableBuilder
    {
        $builder = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getCellStyle()->setFontBold())
            ->setGroupListener($this)
            ->addColumn(PdfColumn::left($this->trans('user.rights.table_title'), 50));
        $permissions = EntityPermission::sorted();
        foreach ($permissions as $permission) {
            $builder->addColumn(PdfColumn::center($this->trans($permission), 25, true));
        }
        $builder->outputHeaders();

        return $builder;
    }

    private function getApplication(): ApplicationService
    {
        return $this->controller->getApplication();
    }

    /**
     * Gets the cell text for the given rights and attribute.
     *
     * @psalm-param FlagBag<EntityPermission>|null $rights
     */
    private function getRightText(?FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights instanceof FlagBag && $rights->hasFlags($permission) ? PdfStyle::BULLET : null;
    }

    /**
     * Output rights.
     *
     * @psalm-param FlagBag<EntityPermission>|null $rights
     */
    private function outputRights(PdfGroupTableBuilder $table, EntityName $entity, ?FlagBag $rights): self
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
     *
     * @throws PdfException
     */
    private function outputRole(PdfGroupTableBuilder $table, Role|User $role): void
    {
        $outputUsers = $role->isAdmin();
        $entities = EntityName::sorted();
        $lines = \count($entities) - 1;
        if (!$outputUsers) {
            --$lines;
        }
        if (!$this->isPrintable((float) $lines * self::LINE_HEIGHT)) {
            $this->AddPage();
        }
        if ($role instanceof User) {
            $this->addBookmark($role->getUserIdentifier(), true);
        } else {
            $this->addBookmark($this->translateRole($role));
        }
        $table->setGroupKey($role);
        foreach ($entities as $entity) {
            if (EntityName::LOG === $entity) {
                continue;
            }
            if ($outputUsers || EntityName::USER !== $entity) {
                $value = $entity->value;
                /** @psalm-var FlagBag<EntityPermission>|null $rights */
                $rights = $role->{$value};
                $this->outputRights($table, $entity, $rights);
            }
        }
    }

    /**
     * Output default rights for the administrator role.
     *
     * @param PdfGroupTableBuilder $table the builder to output to
     *
     * @throws PdfException
     */
    private function outputRoleAdmin(PdfGroupTableBuilder $table): void
    {
        $this->outputRole($table, $this->getApplication()->getAdminRole());
    }

    /**
     * Output default rights for the user role.
     *
     * @param PdfGroupTableBuilder $table the builder to output to
     *
     * @throws PdfException
     */
    private function outputRoleUser(PdfGroupTableBuilder $table): void
    {
        $this->outputRole($table, $this->getApplication()->getUserRole());
    }

    /**
     * Output rights for users.
     *
     * @param User[]               $users the users
     * @param PdfGroupTableBuilder $table the builder to output to
     *
     * @throws PdfException
     */
    private function outputUsers(array $users, PdfGroupTableBuilder $table): void
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

    private function renderTotal(PdfTableBuilder $table, array $entities): void
    {
        $roles = $this->translateCount(2, 'counters.roles');
        $users = $this->translateCount($entities, 'counters.users');
        $text = \sprintf('%s, %s', $roles, $users);
        $table->singleLine($text, PdfStyle::getHeaderStyle(), PdfTextAlignment::LEFT);
    }
}
