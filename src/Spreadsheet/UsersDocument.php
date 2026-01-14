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
use App\Service\RoleService;
use App\Traits\ImageSizeTrait;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use Symfony\Component\Clock\DatePoint;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Spreadsheet document for the list of users.
 *
 * @extends AbstractArrayDocument<User>
 */
class UsersDocument extends AbstractArrayDocument
{
    use ImageSizeTrait;

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly RoleService $roleService,
        private readonly StorageInterface $storage
    ) {
        parent::__construct($controller, $entities);
    }

    /**
     * @param User[] $entities
     */
    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->start('user.list.title');
        $sheet = $this->getActiveSheet();
        $this->createEnabledConditionals($sheet);

        $row = $sheet->setHeaders([
            'user.fields.imageFile' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'user.fields.username' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'user.fields.email' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'user.fields.role' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'user.fields.enabled' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'user.fields.lastLogin' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
        ]);
        $sheet->setFormatBoolean(5, 'common.value_enabled', 'common.value_disabled', true)
            ->setColumnAlignment(5, Alignment::HORIZONTAL_LEFT);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row, [
                null,
                $entity->getUserIdentifier(),
                $entity->getEmail(),
                $this->roleService->translateRole($entity),
                $entity->isEnabled(),
                $this->formatLastLogin($entity->getLastLogin()),
            ]);
            $path = $entity->getImagePath($this->storage);
            if (null !== $path && FileUtils::exists($path)) {
                $size = $this->getImageSize($path)
                    ->resize(32);
                $sheet->setCellImage($path, 'A' . $row, $size);
            }
            ++$row;
        }
        $sheet->finish();

        return true;
    }

    /**
     * Creates a conditional.
     *
     * @param string $value the conditional value
     * @param string $color the conditional color
     */
    private function createConditional(string $value, string $color): Conditional
    {
        $conditional = new Conditional();
        $style = $conditional->setConditionType(Conditional::CONDITION_CELLIS)
            ->setOperatorType(Conditional::OPERATOR_EQUAL)
            ->addCondition($value)
            ->getStyle();
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP);
        $style->getFont()
            ->getColor()
            ->setARGB($color);

        return $conditional;
    }

    /**
     * Sets the enabled/disable conditionals.
     */
    private function createEnabledConditionals(WorksheetDocument $sheet): void
    {
        $disabled = $this->createConditional('0', Color::COLOR_RED);
        $enabled = $this->createConditional('1', Color::COLOR_DARKGREEN);
        $sheet->setColumnConditional(5, $disabled, $enabled);
    }

    /**
     * Format the last login date.
     */
    private function formatLastLogin(?DatePoint $date): string
    {
        if ($date instanceof DatePoint) {
            return FormatUtils::formatDateTime($date);
        }

        return $this->trans('common.value_none');
    }
}
