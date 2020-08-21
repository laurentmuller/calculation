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

use App\Controller\AbstractController;
use App\Entity\Role;
use App\Entity\User;
use App\Interfaces\EntityVoterInterface;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupListenerInterface;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Security\EntityVoter;
use App\Util\Utils;

/**
 * Report for the list of user rights.
 *
 * @author Laurent Muller
 */
class UsersRightsReport extends AbstractReport implements PdfGroupListenerInterface
{
    /**
     * The ASCII bullet character.
     */
    public const BULLET_ASCII = 183;

    /**
     * The attribute names.
     */
    private const ATTRIBUTES = [
        EntityVoterInterface::ATTRIBUTE_LIST,
        EntityVoterInterface::ATTRIBUTE_SHOW,
        EntityVoterInterface::ATTRIBUTE_ADD,
        EntityVoterInterface::ATTRIBUTE_EDIT,
        EntityVoterInterface::ATTRIBUTE_DELETE,
        EntityVoterInterface::ATTRIBUTE_PDF,
    ];

    /**
     * The title and rights.
     */
    private const RIGHTS = [
        'calculation.name' => EntityVoterInterface::ENTITY_CALCULATION,
        'product.name' => EntityVoterInterface::ENTITY_PRODUCT,
        'category.name' => EntityVoterInterface::ENTITY_CATEGORY,
        'calculationstate.name' => EntityVoterInterface::ENTITY_CALCULATION_STATE,
        'globalmargin.name' => EntityVoterInterface::ENTITY_GLOBAL_MARGIN,
        'customer.name' => EntityVoterInterface::ENTITY_CUSTOMER,
        'user.name' => EntityVoterInterface::ENTITY_USER,
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
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('user.rights.title', [], true);
    }

    /**
     * {@inheritdoc}
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        $key = $group->getKey();
        $description = $this->translator->trans('user.fields.role') . ' ';

        if ($key instanceof Role) {
            $description .= Utils::translateRole($this->translator, $key->getRole());
            $parent->singleLine($description, $group->getStyle());

            return true;
        }

        if ($key instanceof User) {
            $text = $key->getUsername();
            if ($key->isEnabled()) {
                $description .= Utils::translateRole($this->translator, $key->getRole());
            } else {
                $description .= $this->translator->trans('common.value_disabled');
            }

            // save position
            [$x, $y] = $this->GetXY();

            // border
            $group->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, '', self::BORDER_ALL);

            // text
            $this->SetXY($x, $y);
            $width = $this->GetStringWidth($text);
            $this->Cell($width, self::LINE_HEIGHT, $text);

            // description
            PdfStyle::getDefaultStyle()->setFontItalic()->apply($this);
            $this->Cell(0, self::LINE_HEIGHT, ' - ' . $description, self::BORDER_NONE, self::MOVE_TO_NEW_LINE);

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
        return $this->renderCount($count);
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
     * @param Role                 $role    the role to output
     */
    private function outputRole(PdfGroupTableBuilder $builder, Role $role): void
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
        $builder->setGroupKey($role);

        // rights
        foreach (self::RIGHTS as $key => $value) {
            if ($outputUsers || EntityVoterInterface::ENTITY_USER !== $value) {
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
        $this->outputRole($builder, EntityVoter::getRoleAdmin());

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
        $this->outputRole($builder, EntityVoter::getRoleUser());

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
            $builder->setGroupKey($user);

            // update rights
            if (!$user->isOverwrite()) {
                $rights = EntityVoter::getRole($user)->getRights();
                $user->setRights($rights);
            }

            // output rights
            foreach (self::RIGHTS as $key => $value) {
                if ($outputUsers || EntityVoterInterface::ENTITY_USER !== $value) {
                    $this->outputRights($builder, $key, $user->{$value});
                }
            }
        }

        return \count($users);
    }
}
