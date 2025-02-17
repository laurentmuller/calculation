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
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Traits\ArrayTrait;
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
    use ArrayTrait;
    use RoleTranslatorTrait;

    private readonly ApplicationService $applicationService;
    private readonly bool $superAdmin;

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly RoleBuilderService $service
    ) {
        parent::__construct($controller, $entities);
        $this->applicationService = $controller->getApplicationService();
        $this->superAdmin = $this->anyMatch($entities, static fn (User $user): bool => $user->isSuperAdmin());
    }

    /**
     * @param User[] $entities
     */
    #[\Override]
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

        $this->outputRoles($sheet, $row);
        $this->outputUsers($sheet, $entities, $row);

        foreach (\range(2, \count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)
                ->setAutoSize(false)
                ->setWidth(11);
        }
        $sheet->finish();

        return true;
    }

    /**
     * @return EntityName[]
     */
    private function getEntityNames(RoleInterface $role): array
    {
        $names = $this->removeValue(EntityName::sorted(), EntityName::LOG);
        if (!$role->isAdmin()) {
            $names = $this->removeValue($names, EntityName::USER);
        }
        if (!$this->applicationService->isDebug()) {
            $names = $this->removeValue($names, EntityName::CUSTOMER);
        }

        return $names;
    }

    /**
     * @psalm-param FlagBag<EntityPermission> $rights
     */
    private function getRightText(FlagBag $rights, EntityPermission $permission): ?string
    {
        return $rights->hasFlags($permission) ? 'x' : null;
    }

    private function outputEntityName(WorksheetDocument $sheet, Role|User $entity, int $row): void
    {
        $role = $this->translateRole($entity);
        if ($entity instanceof User) {
            $text = $entity->getUserIdentifier();
            $description = $entity->isEnabled() ? $role : $this->trans('common.value_disabled');
            $richText = new RichText();
            $richText->createTextRun($text)->getFont()?->setBold(true);
            $richText->createTextRun(' - ' . $description)->getFont()?->setBold(false);
            $sheet->setCellContent(1, $row, $richText);
        } else {
            $sheet->setCellContent(1, $row, $role);
            $sheet->getStyle([1, $row])->getFont()->setBold(true);
        }
    }

    /**
     * @psalm-param FlagBag<EntityPermission> $rights
     */
    private function outputRights(WorksheetDocument $sheet, EntityName $entity, FlagBag $rights, int $row): void
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

    private function outputRole(WorksheetDocument $sheet, Role|User $entity, int &$row): void
    {
        $this->outputEntityName($sheet, $entity, $row++);
        $names = $this->getEntityNames($entity);
        foreach ($names as $name) {
            $rights = $entity->getPermission($name);
            $this->outputRights($sheet, $name, $rights, $row++);
        }
    }

    private function outputRoles(WorksheetDocument $sheet, int &$row): void
    {
        if ($this->superAdmin) {
            $this->outputRole($sheet, $this->service->getRoleSuperAdmin(), $row);
        }
        $service = $this->controller->getApplicationService();
        $this->outputRole($sheet, $service->getAdminRole(), $row);
        $this->outputRole($sheet, $service->getUserRole(), $row);
    }

    private function outputUser(WorksheetDocument $sheet, User $user, int &$row): void
    {
        if (!$user->isOverwrite()) {
            $rights = $this->service->getRole($user)->getRights();
            $user->setRights($rights);
        }
        $this->outputRole($sheet, $user, $row);
    }

    /**
     * @param User[] $users
     */
    private function outputUsers(WorksheetDocument $sheet, array $users, int &$row): void
    {
        foreach ($users as $user) {
            $this->outputUser($sheet, $user, $row);
        }
    }
}
