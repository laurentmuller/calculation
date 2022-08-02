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
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupListenerInterface;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Service\ApplicationService;
use App\Traits\RoleTranslatorTrait;
use App\Util\RoleBuilder;
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
     * The ASCII bullet character.
     */
    private const BULLET_ASCII = 183;

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

            // save position
            [$x, $y] = $this->GetXY();

            // border
            $group->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, '', PdfBorder::all());

            // text
            $this->SetXY($x, $y);
            $width = $this->GetStringWidth($text);
            $this->Cell($width, self::LINE_HEIGHT, $text);

            // description
            PdfStyle::getDefaultStyle()->setFontItalic()->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, ' - ' . $description, PdfBorder::none(), PdfMove::NEW_LINE);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param User[] $entities
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function doRender(array $entities): bool
    {
        $count = 0;

        // title
        $this->setTitleTrans('user.rights.title', [], true);

        // new page
        $this->AddPage();

        // create styles
        $this->titleStyle = PdfStyle::getCellStyle()->setIndent(2);
        $this->rightStyle = PdfStyle::getCellStyle()->setFontName(PdfFont::NAME_SYMBOL);

        // create table
        $builder = $this->createTableBuilder();

        // default rights for administrator role
        $count += $this->outputRoleAdmin($builder);

        // default rights for user role
        $count += $this->outputRoleUser($builder);

        // user rights
        $count += $this->outputUsers($entities, $builder);

        // count
        return $this->renderCount($count);
    }

    /**
     * Creates the right table builder.
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function createTableBuilder(): PdfGroupTableBuilder
    {
        $builder = new PdfGroupTableBuilder($this);
        $style = PdfStyle::getCellStyle()->setFontBold();

        $builder->setGroupStyle($style)
            ->setGroupListener($this)
            ->addColumn(PdfColumn::left($this->trans('user.rights.table_title'), 50));
        $permissions = EntityPermission::sorted();
        foreach ($permissions as $permission) {
            $builder->addColumn(PdfColumn::center($this->trans($permission->getReadable()), 25, true));
        }
        $builder->outputHeaders();

        return $builder;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
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
        return null !== $rights && $rights->hasFlags($permission) ? \chr(self::BULLET_ASCII) : null;
    }

    /**
     * Output rights.
     *
     * @param FlagBag<EntityPermission>|null $rights
     */
    private function outputRights(PdfGroupTableBuilder $builder, string $title, ?FlagBag $rights): self
    {
        $builder->startRow()
            ->add(text: $this->trans($title), style: $this->titleStyle);
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
        // allow outputting user entity rights
        $outputUsers = $role->isAdmin();

        // check new page
        $entities = EntityName::sorted();
        $lines = \count($entities) - 1;
        if (!$outputUsers) {
            --$lines;
        }
        if (!$this->isPrintable($lines * self::LINE_HEIGHT)) {
            $this->AddPage();
        }

        // group
        $builder->setGroupKey($role);

        // rights
        foreach ($entities as $entity) {
            if (EntityName::LOG === $entity) {
                continue;
            }
            if ($outputUsers || EntityName::USER !== $entity) {
                $value = $entity->value;
                /** @psalm-var FlagBag<EntityPermission>|null $rights */
                $rights = $role->{$value};
                $this->outputRights($builder, $entity->getReadable(), $rights);
            }
        }
    }

    /**
     * Output default rights for the administrator role.
     *
     * @param PdfGroupTableBuilder $builder the builder to output to
     *
     * @return int this function returns always 1
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
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
        // users?
        if (empty($users)) {
            return 0;
        }

        // render
        foreach ($users as $user) {
            // update rights
            if (!$user->isOverwrite()) {
                $rights = RoleBuilder::getRole($user)->getRights();
                $user->setRights($rights);
            }

            $this->outputRole($builder, $user);
        }

        return \count($users);
    }
}
