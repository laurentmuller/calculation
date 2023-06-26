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

use App\Controller\AbstractController;
use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Model\Role;
use App\Service\RoleBuilderService;
use App\Traits\RoleTranslatorTrait;
use Elao\Enum\FlagBag;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

/**
 * Spreadsheet document for the list of user rights.
 *
 * @extends AbstractArrayDocument<User>
 */
class UserRightsDocument extends AbstractArrayDocument
{
    use RoleTranslatorTrait;

    /**
     * @param User[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(AbstractController $controller, private readonly RoleBuilderService $builder, array $entities)
    {
        parent::__construct($controller, $entities);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('user.rights.title');

        $sheet = $this->getActiveSheet();
        $permissions = EntityPermission::sorted();
        $headers = ['user.rights.table_title' => HeaderFormat::instance()];
        foreach ($permissions as $permission) {
            $headers[$permission->getReadable()] = HeaderFormat::center();
        }
        $row = $sheet->setHeaders($headers);

        $service = $this->controller->getApplication();
        $this->outputRole($sheet, $service->getAdminRole(), $row);
        $this->outputRole($sheet, $service->getUserRole(), $row);
        foreach ($entities as $entity) {
            $this->outputUser($sheet, $entity, $row);
        }

        foreach (\range(2, \count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)
                ->setAutoSize(false)
                ->setWidth(11);
        }
        $sheet->finish();

        return true;
    }

    /**
     * Gets the cell text for the given rights and attribute.
     *
     * @psalm-param ?FlagBag<\BackedEnum> $rights
     */
    private function getRightText(?FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights instanceof FlagBag && $rights->hasFlags($permission) ? 'x' : null;
    }

    private function outputEntityName(WorksheetDocument $sheet, Role|User $entity, int $row): void
    {
        $role = $this->translateRole($entity);
        if ($entity instanceof User) {
            $text = $entity->getUserIdentifier();
            $description = $entity->isEnabled() ? $role : $this->trans('common.value_disabled');
            $richText = new RichText();
            $richText->createTextRun($text)->getFont()?->setBold(true);
            $richText->createTextRun(' - ' . $description)->getFont()?->setItalic(true);
            $sheet->setCellContent(1, $row, $richText);
        } else {
            $role = $this->trans('user.fields.role') . ' ' . $role;
            $sheet->setCellContent(1, $row, $role);
            $sheet->getStyle([1, $row])->getFont()->setBold(true);
        }
    }

    /**
     * Output the rights.
     *
     * @psalm-param ?FlagBag<\BackedEnum> $rights
     */
    private function outputRights(WorksheetDocument $sheet, EntityName $entity, ?FlagBag $rights, int $row): void
    {
        $columnIndex = 1;
        $sheet->getStyle([$columnIndex, $row])
            ->getAlignment()
            ->setIndent(2);
        $sheet->setCellContent($columnIndex++, $row, $this->trans($entity));
        foreach (EntityPermission::sorted() as $permission) {
            $sheet->setCellContent($columnIndex++, $row, $this->getRightText($rights, $permission));
        }
    }

    /**
     * Output the rights for the given role.
     */
    private function outputRole(WorksheetDocument $sheet, Role|User $entity, int &$row): void
    {
        $names = EntityName::sorted();
        $isAdmin = $entity->isAdmin();
        $this->outputEntityName($sheet, $entity, $row++);

        foreach ($names as $name) {
            if (EntityName::LOG === $name || (!$isAdmin && EntityName::USER === $name)) {
                continue;
            }
            $value = $name->value;
            /** @psalm-var ?FlagBag<\BackedEnum> $rights $rights */
            $rights = $entity->{$value};
            $this->outputRights($sheet, $name, $rights, $row++);
        }
    }

    /**
     * Output the rights for the given user.
     *
     * @param User $user the user to output
     * @param int  $row  the current row
     */
    private function outputUser(WorksheetDocument $sheet, User $user, int &$row): void
    {
        if (!$user->isOverwrite()) {
            $rights = $this->builder->getRole($user)->getRights();
            $user->setRights($rights);
        }
        $this->outputRole($sheet, $user, $row);
    }
}
