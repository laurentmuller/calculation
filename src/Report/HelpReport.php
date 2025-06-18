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
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\HelpService;
use App\Traits\ImageSizeTrait;
use App\Utils\FileUtils;
use fpdf\Enums\PdfTextAlignment;

/**
 * Report for the help documentation.
 *
 * @phpstan-import-type HelpActionType from HelpService
 * @phpstan-import-type HelpForbiddenType from HelpService
 * @phpstan-import-type HelpFieldType from HelpService
 * @phpstan-import-type HelpDialogType from HelpService
 * @phpstan-import-type HelpEntityType from HelpService
 * @phpstan-import-type HelpMainMenuType from HelpService
 * @phpstan-import-type HelpMenuType from HelpService
 */
class HelpReport extends AbstractReport
{
    use ImageSizeTrait;

    private readonly PdfStyle $defaultStyle;
    private readonly PdfStyle $headerStyle;

    /**
     * @param AbstractController $controller the parent controller
     * @param HelpService        $service    the help service
     */
    public function __construct(AbstractController $controller, private readonly HelpService $service)
    {
        parent::__construct($controller);
        $this->setTranslatedTitle('help.title');
        $this->defaultStyle = PdfStyle::default();
        $this->headerStyle = PdfStyle::getHeaderStyle();
    }

    #[\Override]
    public function render(): bool
    {
        $service = $this->service;
        $newPage = $this->outputMainMenus($service->getMainMenus());
        $newPage = $this->outputDialogs($service->getDialogsByGroup(), $newPage);
        $this->outputEntities($service->getEntities(), $newPage);
        $this->addPageIndex();

        return true;
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     *
     * @phpstan-return HelpEntityType|null
     */
    private function findEntity(array $dialog): ?array
    {
        $id = $dialog['entity'] ?? null;

        /** @phpstan-var HelpEntityType|null */
        return $this->service->findEntity($id);
    }

    /**
     * @phpstan-param HelpEntityType|null $entity
     *
     * @phpstan-return HelpFieldType[]|null
     */
    private function findFields(?array $entity): ?array
    {
        return null === $entity ? null : $entity['fields'] ?? null;
    }

    /**
     * @phpstan-param HelpEntityType $item
     * @phpstan-param HelpFieldType $field
     */
    private function formatFieldName(array $item, array $field): string
    {
        $id = $item['id'];
        $name = $field['name'];

        return $this->trans("$id.fields.$name");
    }

    /**
     * @phpstan-param HelpFieldType $field
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
     * @phpstan-param HelpActionType[] $actions
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
            $this->addPage();
        } else {
            $this->lineBreak(3);
        }
        $this->outputText($description);
        $table = PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('help.fields.action', 70, true),
                $this->leftColumn('help.fields.description', 50)
            )->outputHeaders();

        foreach ($actions as $action) {
            /** @phpstan-var HelpActionType $action */
            $action = $this->service->mergeAction($action);
            $table->addRow(
                $this->trans($action['id']),
                $action['description']
            );
        }
    }

    /**
     * @phpstan-param HelpEntityType $item
     * @phpstan-param HelpFieldType[] $fields
     */
    private function outputColumns(array $item, array $fields): void
    {
        if ([] === $fields) {
            return;
        }

        $table = PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('help.fields.column', 30, true),
                $this->leftColumn('help.fields.description', 50)
            )->outputHeaders();
        foreach ($fields as $field) {
            $table->addRow(
                $this->formatFieldName($item, $field),
                $field['description']
            );
        }
    }

    /**
     * @phpstan-param string[] $constraints
     */
    private function outputConstraints(array $constraints): void
    {
        if ([] === $constraints) {
            return;
        }
        $margin = $this->getLeftMargin();
        $this->setLeftMargin($margin + 4.0);
        foreach ($constraints as $constraint) {
            $this->multiCell(text: \strip_tags("- $constraint"), align: PdfTextAlignment::LEFT);
        }
        $this->setLeftMargin($margin);
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialog(array $item): void
    {
        $name = $this->splitTrans($item);
        $this->addBookmark($name, true, 2, false);
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
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogDescription(array $item): void
    {
        if (!isset($item['description'])) {
            return;
        }
        $this->multiCell(text: $item['description']);
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogDetails(array $item): void
    {
        $details = $item['details'] ?? [];
        if ([] === $details) {
            return;
        }
        $this->lineBreak(3);
        $this->outputText('help.labels.description');
        foreach ($details as $detail) {
            $this->multiCell(text: $detail, align: PdfTextAlignment::LEFT);
        }
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogEditActions(array $item): void
    {
        if (!isset($item['editActions'])) {
            return;
        }
        $this->outputActions($item['editActions'], 'help.labels.edit_actions');
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogEntityAndFields(array $item): void
    {
        $entity = $this->findEntity($item);
        $fields = $this->findFields($entity);
        if (null === $entity || null === $fields) {
            return;
        }
        if (isset($item['displayEntityColumns'])) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_columns');
            $this->outputColumns($entity, $fields);
        }
        if (isset($item['displayEntityFields'])) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($entity, $fields);
        }
        $displayEntityActions = $item['displayEntityActions'] ?? false;
        if ($displayEntityActions && isset($entity['actions'])) {
            $this->outputActions($entity['actions'], 'help.labels.entity_actions');
        }
    }

    /**
     * @phpstan-param HelpDialogType $item
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
            $this->addPage();
        } else {
            $this->lineBreak(3);
        }

        $this->multiCell(text: $text);
        $table = PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('help.fields.column', 30, true),
                $this->leftColumn('help.fields.description', 50),
            )->outputHeaders();
        foreach ($fields as $field) {
            $table->addRow(
                $this->splitTrans($field['name']),
                $field['description'],
            );
        }
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogForbidden(array $item): void
    {
        if (!isset($item['forbidden'])) {
            return;
        }
        $forbidden = $item['forbidden'];
        $this->lineBreak(3);
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
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogGlobalActions(array $item): void
    {
        if (!isset($item['globalActions'])) {
            return;
        }
        $this->outputActions($item['globalActions'], 'help.labels.global_actions');
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogImage(array $item): void
    {
        if (!isset($item['image'])) {
            return;
        }
        $this->lineBreak(3);
        $this->outputText('help.labels.screenshot');
        $this->outputImage($item['image']);
    }

    /**
     * @phpstan-param HelpDialogType $item
     */
    private function outputDialogImages(array $item): void
    {
        $images = $item['images'] ?? [];
        if ([] === $images) {
            return;
        }
        foreach ($images as $image) {
            $this->lineBreak(3);
            $this->outputImage($image);
        }
    }

    /**
     * @phpstan-param array<string, HelpDialogType[]> $groupedDialogs
     */
    private function outputDialogs(array $groupedDialogs, bool $newPage): bool
    {
        if ([] === $groupedDialogs) {
            return false;
        }

        if ($newPage) {
            $this->addPage();
            $newPage = false;
        }

        $id = 'help.dialog_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();

        foreach ($groupedDialogs as $group => $dialogs) {
            if ($newPage) {
                $this->addPage();
                $newPage = false;
            }
            $this->addBookmark($group, true, 1, false);
            foreach ($dialogs as $dialog) {
                if ($newPage) {
                    $this->addPage();
                }
                $this->outputDialog($dialog);
                $newPage = true;
            }
        }

        return true;
    }

    /**
     * @phpstan-param HelpEntityType[] $entities
     */
    private function outputEntities(array $entities, bool $newPage): void
    {
        if ([] === $entities) {
            return;
        }

        if ($newPage) {
            $this->addPage();
            $newPage = false;
        }

        $id = 'help.entity_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();

        foreach ($entities as $entity) {
            if ($newPage) {
                $this->addPage();
            }
            $this->outputEntity($entity);
            $newPage = true;
        }
    }

    /**
     * @phpstan-param HelpEntityType $item
     */
    private function outputEntity(array $item): void
    {
        $id = $item['id'] . '.name';
        $this->addBookmark($this->trans($id), true, 1, false);
        $this->outputTitle($id);
        if (isset($item['description'])) {
            $this->multiCell(text: $item['description']);
        }
        $fields = $this->findFields($item);
        if (null !== $fields) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($item, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }
        if (isset($item['constraints'])) {
            $this->lineBreak(3);
            $this->outputText('help.labels.constraints');
            $this->outputConstraints($item['constraints']);
        }
        if (isset($item['actions'])) {
            $this->outputActions($item['actions'], 'help.labels.entity_actions');
        }
    }

    /**
     * @phpstan-param HelpEntityType $item
     * @phpstan-param HelpFieldType[] $fields
     */
    private function outputFields(array $item, array $fields): void
    {
        if ([] === $fields) {
            return;
        }

        $table = PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('help.fields.field', 30, true),
                $this->leftColumn('help.fields.description', 50),
                $this->leftColumn('help.fields.type', 30, true),
                $this->centerColumn('help.fields.required', 18, true)
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
        $this->image(file: $file, width: $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        PdfStyle::default()->apply($this);
    }

    /**
     * @phpstan-param HelpMenuType[] $menus
     */
    private function outputMainMenus(array $menus): bool
    {
        if ([] === $menus) {
            return false;
        }

        $this->addPage();
        $id = 'help.main_menu';
        $this->addBookmark($this->trans($id), true, 0, false);
        $this->outputTitle($id, 12);
        $this->outputLine();

        $rootMenu = $this->service->getMainMenu();
        if (isset($rootMenu['description'])) {
            $this->outputText($rootMenu['description'], false);
        }
        if (isset($rootMenu['image'])) {
            $this->lineBreak(3);
            $this->outputText('help.labels.screenshot');
            $this->outputImage($rootMenu['image']);
        }

        $this->lineBreak(3);
        $this->outputText('help.labels.edit_actions');
        $table = PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('help.fields.action', 60, true),
                $this->leftColumn('help.fields.description', 50)
            )->outputHeaders();
        $this->outputMenus($table, $menus);

        return true;
    }

    /**
     * @phpstan-param HelpMenuType[] $menus
     */
    private function outputMenus(PdfTable $table, array $menus, int $indent = 0): void
    {
        if ([] === $menus) {
            return;
        }

        $style = PdfStyle::getCellStyle()->setIndent($indent);
        foreach ($menus as $menu) {
            /** @phpstan-var HelpMenuType $menu */
            $menu = $this->service->mergeAction($menu);
            $table->startRow()
                ->add($this->splitTrans($menu), style: $style)
                ->add($menu['description'] ?? null)
                ->endRow();

            /** @phpstan-var HelpMenuType[]|null $sub_menus */
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
            $id = $this->splitTrans($id);
        }
        $this->multiCell(text: $id);
    }

    private function outputTitle(string $id, float $size = 10): void
    {
        $this->headerStyle->setFontSize($size)->apply($this);
        $this->outputText($id);
        $this->defaultStyle->apply($this);
    }

    /**
     * @phpstan-param array{id: string, ...}|string $item
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
