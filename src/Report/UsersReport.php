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
use App\Enums\FontAwesomePath;
use App\Interfaces\DocumentHelperInterface;
use App\Model\FontAwesomeIcon;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlColorName;
use App\Pdf\PdfCell;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\FontAwesomeCellService;
use App\Service\RoleService;
use App\Utils\FormatUtils;
use Symfony\Component\Clock\DatePoint;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Report for the list of users.
 *
 * @extends AbstractArrayReport<User>
 */
class UsersReport extends AbstractArrayReport
{
    private const int IMAGE_SIZE = 24;

    private ?PdfCell $defaultCell = null;
    private ?PdfStyle $disabledStyle = null;
    private ?PdfStyle $enabledStyle = null;
    /** @var array<string, PdfCell> */
    private array $roleCells = [];

    /**
     * @param User[] $entities
     */
    public function __construct(
        DocumentHelperInterface $helper,
        array $entities,
        private readonly StorageInterface $storage,
        private readonly RoleService $roleService,
        private readonly FontAwesomeCellService $fontAwesomeService,
    ) {
        parent::__construct($helper, $entities);
        $this->setTranslatedTitle('user.list.title');
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->createStyles();
        $table = $this->createTable();
        foreach ($entities as $entity) {
            $this->outputEntity($table, $entity);
        }

        return $this->renderCount($table, $entities, 'counters.users');
    }

    private function createStyles(): void
    {
        $this->disabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        $this->enabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGreen());
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                $this->centerColumn('user.fields.imageFile', 18, true),
                $this->leftColumn('user.fields.username', 25),
                $this->leftColumn('user.fields.email', 35),
                $this->leftColumn('user.fields.role', 39, true),
                $this->leftColumn('user.fields.enabled', 18, true),
                $this->leftColumn('user.fields.lastLogin', 28, true)
            )->outputHeaders();
    }

    private function formatEditable(bool $editable): string
    {
        return $this->trans($editable ? 'common.value_enabled' : 'common.value_disabled');
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

    private function getDefaultImageCell(): PdfCell
    {
        if (!$this->defaultCell instanceof PdfCell) {
            $icon = new FontAwesomeIcon(FontAwesomePath::REGULAR, 'circle-xmark');
            $color = HtmlColorName::LIGHT_GREY->value;
            $this->defaultCell = $this->fontAwesomeService->getCell(
                icon: $icon,
                color: $color,
            ) ?? PdfCell::instance();
        }

        return $this->defaultCell;
    }

    /**
     * Gets the image cell for the given user.
     */
    private function getImageCell(User $user): PdfCell
    {
        $path = $user->getImagePath($this->storage);
        if (null === $path || !\file_exists($path)) {
            return $this->getDefaultImageCell();
        }

        return (new PdfImageCell($path))->resize(self::IMAGE_SIZE);
    }

    private function getRoleCell(User $user): PdfCell
    {
        $role = $user->getRole();
        if (isset($this->roleCells[$role])) {
            return $this->roleCells[$role];
        }

        $text = $this->roleService->translateRole($role);
        $icon = $this->roleService->getRoleFontAwesomeIcon($role);
        $cell = $this->fontAwesomeService->getCell(
            icon: $icon,
            text: $text
        ) ?? PdfCell::instance($text);

        return $this->roleCells[$role] = $cell;
    }

    private function outputEntity(PdfTable $table, User $entity): void
    {
        $enabled = $entity->isEnabled();
        $editableText = $this->formatEditable($enabled);
        $editableStyle = $enabled ? $this->enabledStyle : $this->disabledStyle;

        $table->startRow()
            ->addCell($this->getImageCell($entity))
            ->add($entity->getUserIdentifier())
            ->add($entity->getEmail())
            ->addCell($this->getRoleCell($entity))
            ->add(text: $editableText, style: $editableStyle)
            ->add($this->formatLastLogin($entity->getLastLogin()))
            ->endRow();
    }
}
