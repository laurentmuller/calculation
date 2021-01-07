<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Report;

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
class UsersRightsReport extends AbstractArrayReport implements PdfGroupListenerInterface
{
    /**
     * The attribute names.
     */
    private const ATTRIBUTES = [
        EntityVoterInterface::ATTRIBUTE_LIST,
        EntityVoterInterface::ATTRIBUTE_SHOW,
        EntityVoterInterface::ATTRIBUTE_ADD,
        EntityVoterInterface::ATTRIBUTE_EDIT,
        EntityVoterInterface::ATTRIBUTE_DELETE,
        EntityVoterInterface::ATTRIBUTE_EXPORT,
    ];
    /**
     * The ASCII bullet character.
     */
    private const BULLET_ASCII = 183;

    /**
     * The title and entities.
     */
    private const RIGHTS = [
        'calculation.name' => EntityVoterInterface::ENTITY_CALCULATION,
        'product.name' => EntityVoterInterface::ENTITY_PRODUCT,
        'task.name' => EntityVoterInterface::ENTITY_TASK,
        'category.name' => EntityVoterInterface::ENTITY_CATEGORY,
        'group.name' => EntityVoterInterface::ENTITY_GROUP,
        'calculationstate.name' => EntityVoterInterface::ENTITY_CALCULATION_STATE,
        'globalmargin.name' => EntityVoterInterface::ENTITY_GLOBAL_MARGIN,
        'customer.name' => EntityVoterInterface::ENTITY_CUSTOMER,
        'user.name' => EntityVoterInterface::ENTITY_USER,
    ];

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
     * {@inheritdoc}
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        $key = $group->getKey();
        $description = $this->translator->trans('user.fields.role') . ' ';

        if ($key instanceof Role) {
            $description .= Utils::translateRole($this->translator, $key);
            $parent->singleLine($description, $group->getStyle());

            return true;
        }

        if ($key instanceof User) {
            $text = $key->getUsername();
            if ($key->isEnabled()) {
                $description .= Utils::translateRole($this->translator, $key);
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

        // users rights
        $count += $this->outputUsers($entities, $builder);

        // count
        return $this->renderCount($count);
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

        $builder->setGroupStyle($style)
            ->setGroupListener($this)
            ->addColumn(PdfColumn::left($this->trans('user.rights.table_title'), 50))
            ->addColumn(PdfColumn::center($this->trans('rights.list'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.show'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.add'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.edit'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.delete'), 25, true))
            ->addColumn(PdfColumn::center($this->trans('rights.export'), 25, true))
            ->outputHeaders();

        return $builder;
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
     * @param array                $rights  the rights
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
        $lines = \count(self::RIGHTS);
        if ($outputUsers) {
            ++$lines;
        }
        if (!$this->isPrintable($lines * self::LINE_HEIGHT)) {
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
        $service = $this->controller->getApplication();
        $this->outputRole($builder, $service->getAdminRole());

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
        $service = $this->controller->getApplication();
        $this->outputRole($builder, $service->getUserRole());

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

        // render
        foreach ($users as $user) {
            // allow to output user entity rights
            $outputUsers = $user->isAdmin() || $user->isSuperAdmin();

            // keep together
            $lines = \count(self::RIGHTS);
            if ($outputUsers) {
                ++$lines;
            }
            if (!$this->isPrintable($lines * self::LINE_HEIGHT)) {
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
