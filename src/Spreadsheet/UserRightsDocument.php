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

namespace App\Spreadsheet;

use App\Entity\Role;
use App\Entity\User;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\RoleInterface;
use App\Security\EntityVoter;
use App\Util\Utils;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @author Laurent Muller
 */
class UserRightsDocument extends AbstractArrayDocument
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
     * @var bool
     */
    private $writeName;

    /**
     * @var bool
     */
    private $writeRights;

    /**
     * {@inheritdoc}
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): self
    {
        if (1 === $columnIndex && $this->writeName) {
            $values = \explode('|', $value);
            if (2 === \count($values)) {
                $richText = new RichText();
                $richText->createTextRun($values[0])
                    ->getFont()->setBold(true);
                $richText->createTextRun(' - ' . $values[1])
                    ->getFont()->setItalic(true);
                parent::setCellValue($sheet, $columnIndex, $rowIndex, $richText);
            } else {
                parent::setCellValue($sheet, $columnIndex, $rowIndex, $value);
                $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle()
                    ->getFont()->setBold(true);
            }
        } else {
            parent::setCellValue($sheet, $columnIndex, $rowIndex, $value);
            if (1 === $columnIndex && $this->writeRights) {
                $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle()
                    ->getAlignment()->setIndent(2);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        $service = $this->controller->getApplication();

        // initialize
        $this->start('user.rights.title');

        // headers
        $headers = ['user.rights.table_title' => Alignment::HORIZONTAL_GENERAL];
        foreach (self::ATTRIBUTES as $attribute) {
            $headers["rights.$attribute"] = Alignment::HORIZONTAL_CENTER;
        }
        $this->setHeaderValues($headers);

        // rows
        $row = 2;

        // admin role
        $this->outputRole($service->getAdminRole(), $row);

        // user role
        $this->outputRole($service->getUserRole(), $row);

        // users
        foreach ($entities as $entity) {
            $this->outputUser($entity, $row);
        }

        $this->finish();

        return true;
    }

    /**
     * Gets the name for the given entity.
     *
     * RoleInterface $entity the entity
     */
    private function getEntityName(RoleInterface $entity): string
    {
        $role = Utils::translateRole($this->translator, $entity);
        $description = $this->trans('user.fields.role') . ' ';

        if ($entity instanceof Role) {
            return $description . $role;
        }

        if ($entity instanceof User) {
            $text = $entity->getUsername();
            if ($entity->isEnabled()) {
                $description .= $role;
            } else {
                $description .= $this->trans('common.value_disabled');
            }

            return $text . '|' . $description;
        }

        return '';
    }

    /**
     * Gets the cell text for the given rights and attribute.
     *
     * @param array  $rights    the user rights
     * @param string $attribute the attribute name to verify
     */
    private function getRightText(array $rights, string $attribute): ?string
    {
        return isset($rights[$attribute]) ? 'x' : null;
    }

    /**
     * Output the rights.
     *
     * @param string $title  the row title
     * @param array  $rights the rights
     * @param int    $row    the row index
     */
    private function outputRights(string $title, array $rights, int $row): void
    {
        $values = [$this->trans($title)];
        foreach (self::ATTRIBUTES as $attribute) {
            $values[] = $this->getRightText($rights, $attribute);
        }
        $this->setRowValues($row, $values);
    }

    /**
     * Output the rights for the given role.
     *
     * @param Role $role the role to output
     * @param int  $row  the current row
     */
    private function outputRole(Role $role, int &$row): void
    {
        // allow to output user entity rights
        $outputUsers = $role->isAdmin() || $role->isSuperAdmin();

        $this->writeName = true;
        $this->setRowValues($row++, [$this->getEntityName($role)]);
        $this->writeName = false;

        $this->writeRights = true;
        foreach (self::RIGHTS as $key => $value) {
            if ($outputUsers || EntityVoterInterface::ENTITY_USER !== $value) {
                $this->outputRights($key, $role->{$value}, $row++);
            }
        }
        $this->writeRights = false;
    }

    /**
     * Output the rights for the given user.
     *
     * @param User $user the user to output
     * @param int  $row  the current row
     */
    private function outputUser(User $user, int &$row): void
    {
        $this->writeName = true;
        $this->setRowValues($row++, [$this->getEntityName($user)]);
        $this->writeName = false;

        $outputUsers = $user->isAdmin() || $user->isSuperAdmin();
        if (!$user->isOverwrite()) {
            $rights = EntityVoter::getRole($user)->getRights();
            $user->setRights($rights);
        }

        $this->writeRights = true;
        foreach (self::RIGHTS as $key => $value) {
            if ($outputUsers || EntityVoterInterface::ENTITY_USER !== $value) {
                $this->outputRights($key, $user->{$value}, $row++);
            }
        }
        $this->writeRights = false;
    }
}
