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

namespace App\Spreadsheet;

use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Traits\RoleTranslatorTrait;
use App\Util\RoleBuilder;
use Elao\Enum\FlagBag;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Spreadsheet document for the list of user rights.
 *
 * @extends AbstractArrayDocument<User>
 */
class UserRightsDocument extends AbstractArrayDocument
{
    use RoleTranslatorTrait;

    private ?bool $writeName = null;
    private ?bool $writeRights = null;

    /**
     * {@inheritdoc}
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): static
    {
        if (1 === $columnIndex && $this->writeName) {
            $values = \explode('|', (string) $value);
            if (2 === \count($values)) {
                $richText = new RichText();
                $font = $richText->createTextRun($values[0])
                    ->getFont();
                $font?->setBold(true);
                $font = $richText->createTextRun(' - ' . $values[1])
                    ->getFont();
                $font?->setItalic(true);
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
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function doRender(array $entities): bool
    {
        $service = $this->controller->getApplication();

        // initialize
        $this->start('user.rights.title');

        // headers
        $permissions = EntityPermission::sorted();
        $headers = ['user.rights.table_title' => Alignment::HORIZONTAL_GENERAL];
        foreach ($permissions as $permission) {
            $headers[$permission->getReadable()] = Alignment::HORIZONTAL_CENTER;
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

        // width
        $sheet = $this->getActiveSheet();
        foreach (\range(2, \count($headers)) as $column) {
            $name = $this->stringFromColumnIndex($column);
            $sheet->getColumnDimension($name)->setAutoSize(false)
                ->setWidth(11);
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
        $role = $this->translateRole($entity);
        $description = $this->trans('user.fields.role') . ' ';

        if ($entity instanceof Role) {
            return $description . $role;
        }

        if ($entity instanceof User) {
            $text = $entity->getUserIdentifier();
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
     * @psalm-param ?FlagBag<\BackedEnum> $rights
     */
    private function getRightText(?FlagBag $rights, EntityPermission $permission): ?string
    {
        return null !== $rights && $rights->hasFlags($permission) ? 'x' : null;
    }

    /**
     * Output the rights.
     *
     * @psalm-param ?FlagBag<\BackedEnum> $rights
     */
    private function outputRights(string $title, ?FlagBag $rights, int $row): void
    {
        $values = [$this->trans($title)];
        foreach (EntityPermission::sorted() as $permission) {
            $values[] = $this->getRightText($rights, $permission);
        }
        $this->setRowValues($row, $values);
    }

    /**
     * Output the rights for the given role.
     */
    private function outputRole(Role|User $role, int &$row): void
    {
        // allow output user entity rights
        $outputUsers = $role->isAdmin();

        $this->writeName = true;
        $this->setRowValues($row++, [$this->getEntityName($role)]);
        $this->writeName = false;

        $this->writeRights = true;
        $entities = EntityName::sorted();
        foreach ($entities as $entity) {
            if (EntityName::LOG === $entity) {
                continue;
            }
            if ($outputUsers || EntityName::USER !== $entity) {
                $value = $entity->value;
                /** @psalm-var ?FlagBag<\BackedEnum> $rights $rights */
                $rights = $role->{$value};
                $this->outputRights($entity->getReadable(), $rights, $row++);
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
        if (!$user->isOverwrite()) {
            $rights = RoleBuilder::getRole($user)->getRights();
            $user->setRights($rights);
        }
        $this->outputRole($user, $row);
    }
}
