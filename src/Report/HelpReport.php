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

use App\Controller\AbstractController;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\HelpService;
use App\Traits\ImageSizeTrait;
use App\Utils\FileUtils;

/**
 * Report for the help documentation.
 *
 * @psalm-import-type HelpActionType from HelpService
 * @psalm-import-type HelpForbiddenType from HelpService
 * @psalm-import-type HelpFieldType from HelpService
 * @psalm-import-type HelpDialogType from HelpService
 * @psalm-import-type HelpEntityType from HelpService
 * @psalm-import-type HelpMainMenuType from HelpService
 * @psalm-import-type HelpMenuType from HelpService
 */
class HelpReport extends AbstractReport
{
    use ImageSizeTrait;

    /**
     * @param AbstractController $controller the parent controller
     * @param HelpService        $service    the help service
     */
    public function __construct(AbstractController $controller, private readonly HelpService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('help.title');
    }

    /**
     * @throws PdfException
     */
    public function render(): bool
    {
        $service = $this->service;
        $newPage = $this->outputMainMenus($service->getMainMenus());
        $newPage = $this->outputDialogs($service->getDialogs(), $newPage);
        $this->outputEntities($service->getEntities(), $newPage);
        $this->addPageIndex();

        return true;
    }

    /**
     * @psalm-param HelpDialogType $dialog
     *
     * @psalm-return HelpEntityType|null
     */
    private function findEntity(array $dialog): ?array
    {
        $id = $dialog['entity'] ?? null;
        if (null !== $id) {
            /** @psalm-var HelpEntityType|null $entity */
            $entity = $this->service->findEntity($id);
            if (null !== $entity) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @psalm-param HelpEntityType|null $entity
     *
     * @psalm-return HelpFieldType[]|null
     */
    private function findFields(?array $entity): ?array
    {
        return null === $entity ? null : $entity['fields'] ?? null;
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType $field
     */
    private function formatFieldName(array $item, array $field): string
    {
        $id = $item['id'];
        $name = $field['name'];

        return $this->trans("$id.fields.$name");
    }

    /**
     * @psalm-param HelpFieldType $field
     */
    private function formatFieldType(array $field): string
    {
        $default = $field['type'] ?? 'text';
        $type = $this->trans("help.types.$default");
        if (isset($field['length'])) {
            return \sprintf('%s (%s)', $type, $field['length']);
        }

        return $type;
    }

    private function formatRequired(bool $required): string
    {
        return $this->trans($required ? 'common.value_true' : 'common.value_false');
    }

    /**
     * @psalm-param HelpActionType $action
     *
     * @psalm-return HelpActionType
     */
    private function mergeAction(array $action): array
    {
        if (!isset($action['action'])) {
            return $action;
        }
        $key = $action['action'];
        $source = $this->service->findAction($key);
        if (null === $source) {
            return $action;
        }

        return \array_merge($action, $source);
    }

    /**
     * @psalm-param HelpActionType[] $actions
     */
    private function outputActions(array $actions, string $description): void
    {
        if ([] === $actions) {
            return;
        }
        $lines = 1 + \count($actions);
        if ('' !== $description) {
            $lines += $this->getLinesCount($description);
        }
        $height = (float) $lines * self::LINE_HEIGHT + 3.0;
        if (!$this->isPrintable($height)) {
            $this->AddPage();
        } else {
            $this->Ln(3);
        }
        $this->outputText($description);
        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('help.fields.action'), 70, true),
                PdfColumn::left($this->trans('help.fields.description'), 50)
            )->outputHeaders();

        foreach ($actions as $action) {
            $action = $this->mergeAction($action);
            $table->addRow(
                $this->trans($action['id']),
                $action['description']
            );
        }
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType[] $fields
     */
    private function outputColumns(array $item, array $fields): void
    {
        if ([] === $fields) {
            return;
        }

        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('help.fields.column'), 30, true),
                PdfColumn::left($this->trans('help.fields.description'), 50)
            )->outputHeaders();
        foreach ($fields as $field) {
            $table->addRow(
                $this->formatFieldName($item, $field),
                $field['description']
            );
        }
    }

    /**
     * @psalm-param string[] $constraints
     */
    private function outputConstraints(array $constraints): void
    {
        if ([] === $constraints) {
            return;
        }
        $margin = $this->getLeftMargin();
        $this->SetLeftMargin($margin + 4.0);
        foreach ($constraints as $constraint) {
            $this->MultiCell(txt: \strip_tags("- $constraint"), align: PdfTextAlignment::LEFT);
        }
        $this->SetLeftMargin($margin);
    }

    /**
     * @psalm-param HelpDialogType $item
     *
     * @throws PdfException
     */
    private function outputDialog(array $item): void
    {
        $name = $this->splitTrans($item);
        $this->addBookmark($name, true, 1, false);
        $this->outputTitle($name);
        $this->outputDialogDescription($item);
        $this->outputDialogImage($item);
        $this->outputDialogImages($item);
        $this->outputDialogDetails($item);
        $this->outputDialogFields($item);
        $this->outputDialogEntityAndFields($item);
        $this->outputDialogEditActions($item);
        $this->outputDialogGlobalActions($item);
        $this->outputDialogForbidden($item);
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogDescription(array $item): void
    {
        if (!isset($item['description'])) {
            return;
        }
        $this->MultiCell(txt: $item['description']);
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogDetails(array $item): void
    {
        $details = $item['details'] ?? [];
        if ([] === $details) {
            return;
        }
        $this->Ln(3);
        $this->outputText('help.labels.description');
        foreach ($details as $detail) {
            $this->MultiCell(txt: $detail, align: PdfTextAlignment::LEFT);
        }
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogEditActions(array $item): void
    {
        if (!isset($item['editActions'])) {
            return;
        }
        $this->outputActions($item['editActions'], 'help.labels.edit_actions');
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogEntityAndFields(array $item): void
    {
        $entity = $this->findEntity($item);
        $fields = $this->findFields($entity);
        if (null === $entity || null === $fields) {
            return;
        }
        if (isset($item['displayEntityColumns'])) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_columns');
            $this->outputColumns($entity, $fields);
        }
        if (isset($item['displayEntityFields'])) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($entity, $fields);
        }
        $displayEntityActions = $item['displayEntityActions'] ?? false;
        if ($displayEntityActions && isset($entity['actions'])) {
            $this->outputActions($entity['actions'], 'help.labels.entity_actions');
        }
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogFields(array $item): void
    {
        $fields = $item['fields'] ?? [];
        if ([] === $fields) {
            return;
        }

        $text = $this->trans('help.labels.edit_columns');
        $lines = 1 + \count($fields) + $this->getLinesCount($text);
        $height = (float) $lines * self::LINE_HEIGHT + 3.0;
        if (!$this->isPrintable($height)) {
            $this->AddPage();
        } else {
            $this->Ln(3);
        }

        $this->MultiCell(txt: $text);
        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('help.fields.column'), 30, true),
                PdfColumn::left($this->trans('help.fields.description'), 50),
            )->outputHeaders();
        foreach ($fields as $field) {
            $table->addRow(
                $this->splitTrans($field['name']),
                $field['description'],
            );
        }
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogForbidden(array $item): void
    {
        if (!isset($item['forbidden'])) {
            return;
        }
        $forbidden = $item['forbidden'];
        $this->Ln(3);
        $text = $forbidden['text'] ?? $this->trans('help.labels.forbidden_text');
        $this->outputText($text, false);
        $image = $forbidden['image'] ?? null;
        if (null !== $image) {
            $this->outputImage($image);
        }
        if (isset($forbidden['action'])) {
            $this->outputActions([$forbidden['action']], 'help.labels.edit_actions');
        }
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogGlobalActions(array $item): void
    {
        if (!isset($item['globalActions'])) {
            return;
        }
        $this->outputActions($item['globalActions'], 'help.labels.global_actions');
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogImage(array $item): void
    {
        if (!isset($item['image'])) {
            return;
        }
        $this->Ln(3);
        $this->outputText('help.labels.screenshot');
        $this->outputImage($item['image']);
    }

    /**
     * @psalm-param HelpDialogType $item
     */
    private function outputDialogImages(array $item): void
    {
        $images = $item['images'] ?? [];
        if ([] === $images) {
            return;
        }
        foreach ($images as $image) {
            $this->Ln(3);
            $this->outputImage($image);
        }
    }

    /**
     * @psalm-param HelpDialogType[]|null $dialogs
     *
     * @throws PdfException
     */
    private function outputDialogs(?array $dialogs, bool $newPage): bool
    {
        if (null === $dialogs || [] === $dialogs) {
            return false;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $id = 'help.dialog_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();

        foreach ($dialogs as $dialog) {
            if ($newPage) {
                $this->AddPage();
            }
            $newPage = true;
            $this->outputDialog($dialog);
        }

        return true;
    }

    /**
     * @psalm-param HelpEntityType[]|null $entities
     *
     * @throws PdfException
     */
    private function outputEntities(?array $entities, bool $newPage): void
    {
        if (null === $entities || [] === $entities) {
            return;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $id = 'help.entity_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();

        foreach ($entities as $entity) {
            if ($newPage) {
                $this->AddPage();
            }
            $newPage = true;
            $this->outputEntity($entity);
        }
    }

    /**
     * @psalm-param HelpEntityType $item
     *
     * @throws PdfException
     */
    private function outputEntity(array $item): void
    {
        $id = $item['id'] . '.name';
        $this->addBookmark($this->trans($id), true, 1, false);
        $this->outputTitle($id);
        if (isset($item['description'])) {
            $this->MultiCell(txt: $item['description']);
        }
        $fields = $this->findFields($item);
        if (null !== $fields) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($item, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }
        if (isset($item['constraints'])) {
            $this->Ln(3);
            $this->outputText('help.labels.constraints');
            $this->outputConstraints($item['constraints']);
        }
        if (isset($item['actions'])) {
            $this->outputActions($item['actions'], 'help.labels.entity_actions');
        }
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType[] $fields
     */
    private function outputFields(array $item, array $fields): void
    {
        if ([] === $fields) {
            return;
        }

        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('help.fields.field'), 30, true),
                PdfColumn::left($this->trans('help.fields.description'), 50),
                PdfColumn::left($this->trans('help.fields.type'), 30, true),
                PdfColumn::center($this->trans('help.fields.required'), 18, true)
            )->outputHeaders();

        foreach ($fields as $field) {
            $table->addRow(
                $this->formatFieldName($item, $field),
                $field['description'],
                $this->formatFieldType($field),
                $this->formatRequired($field['required'] ?? true)
            );
        }
    }

    private function outputImage(string $image): void
    {
        $file = FileUtils::buildPath($this->service->getImagePath(), $image . HelpService::IMAGES_EXT);
        if (!FileUtils::exists($file)) {
            return;
        }
        $size = $this->getImageSize($file);
        if (0 === $size[0]) {
            return;
        }
        $width = $this->pixels2UserUnit($size[0]);
        $width = \min($width, $this->getPrintableWidth());
        $this->Image(file: $file, w: $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        PdfStyle::default()->apply($this);
    }

    /**
     * @psalm-param HelpMenuType[]|null $menus
     *
     * @throws PdfException
     */
    private function outputMainMenus(?array $menus): bool
    {
        if (null === $menus || [] === $menus) {
            return false;
        }

        $this->AddPage();
        $id = 'help.main_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();
        $rootMenu = $this->service->getMainMenu();
        if (null !== $rootMenu) {
            if (isset($rootMenu['description'])) {
                $this->outputText($rootMenu['description'], false);
            }
            if (isset($rootMenu['image'])) {
                $this->Ln(3);
                $this->outputText('help.labels.screenshot');
                $this->outputImage($rootMenu['image']);
            }
        }
        $this->Ln(3);
        $this->outputText('help.labels.edit_actions');
        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('help.fields.action'), 60, true),
                PdfColumn::left($this->trans('help.fields.description'), 50)
            )->outputHeaders();
        $this->outputMenus($table, $menus);

        return true;
    }

    /**
     * @psalm-param HelpMenuType[] $menus
     */
    private function outputMenus(PdfTable $table, array $menus, int $indent = 0): void
    {
        if ([] === $menus) {
            return;
        }

        $style = PdfStyle::getCellStyle()->setIndent($indent);
        foreach ($menus as $menu) {
            $table->startRow()
                ->add(text: $this->splitTrans($menu), style: $style)
                ->add($menu['description'] ?? null)
                ->endRow();

            /** @psalm-var HelpMenuType[]|null $sub_menus */
            $sub_menus = $menu['menus'] ?? null;
            if (null !== $sub_menus) {
                $this->outputMenus($table, $sub_menus, $indent + 4);
            }
        }
    }

    private function outputText(string $id, bool $translate = true): void
    {
        if ('' === $id) {
            return;
        }

        if ($translate) {
            $id = $this->trans($id);
        }
        $this->MultiCell(txt: $id);
    }

    private function outputTitle(string $id, float $size = 10): void
    {
        PdfStyle::getHeaderStyle()->setFontSize($size)->apply($this);
        $this->outputText($id);
        PdfStyle::default()->apply($this);
    }

    /**
     * @psalm-param array{id: string, ...}|string $item
     */
    private function splitTrans(array|string $item): string
    {
        if (\is_array($item)) {
            $item = $item['id'];
        }
        $values = \explode('|', $item);
        if (2 === \count($values)) {
            return $this->trans($values[0], [], $values[1]);
        }

        return $this->trans($values[0]);
    }
}
