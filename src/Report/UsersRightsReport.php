<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\BaseController;
use App\Entity\Role;
use App\Entity\User;
use App\Interfaces\IEntityVoter;
use App\Pdf\IPdfGroupListener;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Security\EntityVoter;
use App\Utils\Utils;

/**
 * Report for the list of user rights.
 *
 * @author Laurent Muller
 */
class UsersRightsReport extends BaseReport implements IPdfGroupListener
{
    /**
     * The ASCII bullet character.
     */
    public const BULLET_ASCII = 183;

    /**
     * The attribute names.
     */
    private const ATTRIBUTES = [
        IEntityVoter::ATTRIBUTE_LIST,
        IEntityVoter::ATTRIBUTE_SHOW,
        IEntityVoter::ATTRIBUTE_ADD,
        IEntityVoter::ATTRIBUTE_EDIT,
        IEntityVoter::ATTRIBUTE_DELETE,
        IEntityVoter::ATTRIBUTE_PDF,
    ];

    /**
     * The title and rights.
     */
    private const RIGHTS = [
        'calculation.name' => IEntityVoter::ENTITY_CALCULATION,
        'product.name' => IEntityVoter::ENTITY_PRODUCT,
        'category.name' => IEntityVoter::ENTITY_CATEGORY,
        'calculationstate.name' => IEntityVoter::ENTITY_CALCULATION_STATE,
        'globalmargin.name' => IEntityVoter::ENTITY_GLOBAL_MARGIN,
        'customer.name' => IEntityVoter::ENTITY_CUSTOMER,
        'user.name' => IEntityVoter::ENTITY_USER,
    ];

    /**
     * The users to render.
     *
     * @var \App\Entity\User[]
     */
    protected $users;

    /**
     * The right cell style.
     *
     * @var PdfStyle
     */
    private $rightStyle;

    /**
     * The title cell style.
     *
     * @var PdfStyle
     */
    private $titleStyle;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->SetTitleTrans('user.rights.title', [], true);
    }

    /**
     * {@inheritdoc}
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        // find user
        $name = $group->getName();
        if ($user = $this->findUser($name)) {
            // role
            $role = Utils::translateRole($this->translator, $user->getRole());

            // save position
            $x = $this->GetX();
            $y = $this->GetY();

            // border
            $group->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, '', self::BORDER_ALL);
            $this->SetXY($x, $y);

            // name
            $width = $this->GetStringWidth($name);
            $this->Cell($width, self::LINE_HEIGHT, $name);

            // role
            PdfStyle::getDefaultStyle()->setFontItalic()->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, ' - ' . $role, self::BORDER_NONE, self::MOVE_TO_NEW_LINE);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $count = 0;

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

        // users rights
        $count += $this->outputUsers($this->users, $builder);

        // count
        return $this->resetStyle()->renderCount($count);
    }

    /**
     * Sets the users.
     *
     * @param \App\Entity\User[] $users
     */
    public function setUsers(array $users): self
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Creates the rights table builder.
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function createTableBuilder(): PdfGroupTableBuilder
    {
        $builder = new PdfGroupTableBuilder($this);
        $style = PdfStyle::getCellStyle()->setFontBold();

        return $builder->setGroupStyle($style)
            ->setGroupListener($this)
            ->addColumn(PdfColumn::left($this->trans('user.rights.table_title'), 50))
            ->addColumn(PdfColumn::center($this->trans('rights.list'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.show'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.add'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.edit'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.delete'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.pdf'), 25, true))
            ->outputHeaders();
    }

    /**
     * Finds an user by name.
     *
     * @param string $name the user name to find
     *
     * @return User|null the user, if found; null otherwise
     */
    private function findUser(string $name): ?User
    {
        return Utils::findFirst($this->users, function (User $user) use ($name) {
            return $name === $user->getUsername();
        });

        return null;
    }

    /**
     * Gets the cell text for the given rights and attribute.
     *
     * @param array  $rights    the user rights
     * @param string $attribute the attribute name to verify
     */
    private function getRightText(array $rights, string $attribute): ?string
    {
        return isset($rights[$attribute]) ? \chr(self::BULLET_ASCII) : null;
    }

    /**
     * Output rights.
     *
     * @param PdfGroupTableBuilder $builder the table builder
     * @param string               $title   the row title
     * @param array                $rights  the user rights
     */
    private function outputRights(PdfGroupTableBuilder $builder, string $title, array $rights): self
    {
        $builder->startRow()->add($this->trans($title), 1, $this->titleStyle);
        foreach (self::ATTRIBUTES as $attribute) {
            $builder->add($this->getRightText($rights, $attribute), 1, $this->rightStyle);
        }
        $builder->endRow();

        return $this;
    }

    /**
     * Output rights for a role.
     *
     * @param PdfGroupTableBuilder $builder the buider to output to
     * @param Role the role to output
     * @param string the group name
     */
    private function outputRole(PdfGroupTableBuilder $builder, Role $role, string $title): void
    {
        // allow to output user entity rights
        $outputUsers = $role->isAdmin() || $role->isSuperAdmin();

        // check new page
        $linesHeight = \count(self::RIGHTS) * self::LINE_HEIGHT;
        if ($outputUsers) {
            $linesHeight += self::LINE_HEIGHT;
        }
        if (!$this->isPrintable($linesHeight)) {
            $this->AddPage();
        }

        // group
        $builder->setGroupName($title);

        // rights
        foreach (self::RIGHTS as $key => $value) {
            if ($outputUsers || IEntityVoter::ENTITY_USER !== $value) {
                $this->outputRights($builder, $key, $role->{$value});
            }
        }
    }

    /**
     * Output default rights for the administrator role.
     *
     * @param PdfGroupTableBuilder $builder the buider to output to
     *
     * @return int this function returns always 1
     */
    private function outputRoleAdmin(PdfGroupTableBuilder $builder): int
    {
        $role = EntityVoter::getRoleAdmin();
        $title = Utils::translateRole($this->translator, User::ROLE_ADMIN);
        $this->outputRole($builder, $role, $title);

        return 1;
    }

    /**
     * Output default rights for the user role.
     *
     * @param PdfGroupTableBuilder $builder the buider to output to
     *
     * @return int this function returns always 1
     */
    private function outputRoleUser(PdfGroupTableBuilder $builder): int
    {
        $role = EntityVoter::getRoleUser();
        $title = Utils::translateRole($this->translator, User::ROLE_DEFAULT);
        $this->outputRole($builder, $role, $title);

        return 1;
    }

    /**
     * Output rights for users.
     *
     * @param User[]               $users   the users
     * @param PdfGroupTableBuilder $builder the buider to output to
     *
     * @return int the number of users
     */
    private function outputUsers(array $users, PdfGroupTableBuilder $builder): int
    {
        // users?
        if (empty($users)) {
            return 0;
        }

        // sort
        Utils::sortField($users, 'username');

        // render
        foreach ($users as $user) {
            // allow to output user entity rights
            $outputUsers = $user->isAdmin() || $user->isSuperAdmin();

            // keep together
            $linesHeight = \count(self::RIGHTS) * self::LINE_HEIGHT;
            if ($outputUsers) {
                $linesHeight += self::LINE_HEIGHT;
            }
            if (!$this->isPrintable($linesHeight)) {
                $this->AddPage();
            }

            // group
            $builder->setGroupName($user->getUsername());

            // update rights
            $oldRights = $user->getRights();
            if (!$user->isOverwrite()) {
                $rights = EntityVoter::getRole($user)->getRights();
                $user->setRights($rights);
            }

            // output rights
            foreach (self::RIGHTS as $key => $value) {
                if ($outputUsers || IEntityVoter::ENTITY_USER !== $value) {
                    $this->outputRights($builder, $key, $user->{$value});
                }
            }

            // restore
            if (!$user->isOverwrite()) {
                $user->setRights($oldRights);
            }
        }

        return \count($users);
    }
}
