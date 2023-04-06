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

use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Model\Role;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Service\ApplicationService;
use App\Traits\RoleTranslatorTrait;
use App\Utils\RoleBuilder;
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
     * {@inheritdoc}
     */
    public function outputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        /** @var Role|User|null $key */
        $key = $group->getKey();
        $description = $this->trans('user.fields.role') . ' ';
        if ($key instanceof Role) {
            $description .= $this->translateRole($key);
            $parent->singleLine($description, $group->getStyle());

            return true;
        }
        if ($key instanceof User) {
            $text = $key->getUserIdentifier();
            if ($key->isEnabled()) {
                $description .= $this->translateRole($key);
            } else {
                $description .= $this->trans('common.value_disabled');
            }
            [$x, $y] = $this->GetXY();
            $group->apply($this);
            $this->Cell(border: PdfBorder::all());
            $this->SetXY($x, $y);
            $width = $this->GetStringWidth($text);
            $this->Cell(w: $width, txt: $text);
            PdfStyle::getDefaultStyle()->setFontItalic()->apply($this);
            $this->Cell(txt: ' - ' . $description, ln: PdfMove::NEW_LINE);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param User[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $count = 0;
        $this->setTitleTrans('user.rights.title', [], true);
        $this->AddPage();
        $this->rightStyle = PdfStyle::getBulletStyle();
        $this->titleStyle = PdfStyle::getCellStyle()->setIndent(2);
        $builder = $this->createTableBuilder();
        $count += $this->outputRoleAdmin($builder);
        $count += $this->outputRoleUser($builder);
        $count += $this->outputUsers($entities, $builder);

        return $this->renderCount($count);
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
     * @param FlagBag<EntityPermission>|null $rights
     */
    private function getRightText(?FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights instanceof FlagBag && $rights->hasFlags($permission) ? PdfStyle::BULLET : null;
    }

    /**
     * Output rights.
     *
     * @param FlagBag<EntityPermission>|null $rights
     */
    private function outputRights(PdfGroupTableBuilder $builder, EntityName $entity, ?FlagBag $rights): self
    {
        $builder->startRow()
            ->add(text: $this->trans($entity), style: $this->titleStyle);
        foreach (EntityPermission::sorted() as $permission) {
            $builder->add(text: $this->getRightText($rights, $permission), style: $this->rightStyle);
        }
        $builder->endRow();

        return $this;
    }

    /**
     * Output rights for a role.
     */
    private function outputRole(PdfGroupTableBuilder $builder, Role|User $role): void
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
        $builder->setGroupKey($role);
        foreach ($entities as $entity) {
            if (EntityName::LOG === $entity) {
                continue;
            }
            if ($outputUsers || EntityName::USER !== $entity) {
                $value = $entity->value;
                /** @psalm-var FlagBag<EntityPermission>|null $rights */
                $rights = $role->{$value};
                $this->outputRights($builder, $entity, $rights);
            }
        }
    }

    /**
     * Output default rights for the administrator role.
     *
     * @param PdfGroupTableBuilder $builder the builder to output to
     *
     * @return int this function returns always 1
     */
    private function outputRoleAdmin(PdfGroupTableBuilder $builder): int
    {
        $this->outputRole($builder, $this->getApplication()->getAdminRole());

        return 1;
    }

    /**
     * Output default rights for the user role.
     *
     * @param PdfGroupTableBuilder $builder the builder to output to
     *
     * @return int this function returns always 1
     */
    private function outputRoleUser(PdfGroupTableBuilder $builder): int
    {
        $this->outputRole($builder, $this->getApplication()->getUserRole());

        return 1;
    }

    /**
     * Output rights for users.
     *
     * @param User[]               $users   the users
     * @param PdfGroupTableBuilder $builder the builder to output to
     *
     * @return int the number of users
     */
    private function outputUsers(array $users, PdfGroupTableBuilder $builder): int
    {
        if ([] === $users) {
            return 0;
        }
        foreach ($users as $user) {
            if (!$user->isOverwrite()) {
                $rights = RoleBuilder::getRole($user)->getRights();
                $user->setRights($rights);
            }
            $this->outputRole($builder, $user);
        }

        return \count($users);
    }
}
