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
 * @phpstan-import-type HelpLink from HelpService
 */
class HelpReport extends AbstractReport
{
    use ImageSizeTrait;

    private readonly PdfStyle $defaultStyle;
    private readonly PdfStyle $headerStyle;

    /**
     * @phpstan-param HelpDialogType|null $dialog
     * @phpstan-param HelpEntityType|null $entity
     */
    public function __construct(
        AbstractController $controller,
        private readonly HelpService $service,
        private readonly ?array $dialog = null,
        private readonly ?array $entity = null
    ) {
        parent::__construct($controller);
        $this->setTitle($this->buildTitle(), true);
        $this->defaultStyle = PdfStyle::default();
        $this->headerStyle = PdfStyle::getHeaderStyle();
    }

    #[\Override]
    public function render(): bool
    {
        $this->outputMainMenus();
        $this->outputDialogs();
        $this->outputEntities();
        if (!$this->isFilter()) {
            $this->addPageIndex();
        }

        return true;
    }

    private function buildTitle(): string
    {
        $title = $this->trans('help.title');
        if ($this->isFilterDialog()) {
            return $title . ' - ' . $this->getDialogTitle($this->dialog);
        }
        if ($this->isFilterEntity()) {
            return $title . ' - ' . $this->getEntityTitle($this->entity);
        }

        return $title;
    }

    /**
     * @phpstan-return array<string, HelpDialogType[]>
     */
    private function filterDialogs(): array
    {
        if ($this->isFilterDialog()) {
            return [
                $this->dialog['group'] => [$this->dialog],
            ];
        }
        if ($this->isFilterEntity()) {
            return [];
        }

        return $this->service->getDialogsByGroup();
    }

    /**
     * @phpstan-return HelpEntityType[]
     */
    private function filterEntities(): array
    {
        if ($this->isFilterEntity()) {
            return [$this->entity];
        }
        if ($this->isFilterDialog()) {
            return [];
        }

        return $this->service->getEntities();
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     *
     * @phpstan-return HelpEntityType|null
     */
    private function findEntity(array $dialog): ?array
    {
        return $this->service->findEntity($dialog);
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
     * @phpstan-param HelpEntityType $entity
     * @phpstan-param HelpFieldType $field
     */
    private function formatFieldName(array $entity, array $field): string
    {
        $id = $entity['id'];
        $name = $field['name'];

        return $this->trans(\sprintf('%s.fields.%s', $id, $name));
    }

    /**
     * @phpstan-param HelpFieldType $field
     */
    private function formatFieldType(array $field): string
    {
        $default = $field['type'] ?? 'text';
        $type = $this->trans('help.types.' . $default);
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
     * @phpstan-param HelpDialogType $dialog
     */
    private function getDialogTitle(array $dialog): string
    {
        return $this->trans($dialog['id']);
    }

    /**
     * @phpstan-param HelpEntityType $entity
     */
    private function getEntityTitle(array $entity): string
    {
        return $this->trans($entity['id'] . '.name');
    }

    private function isFilter(): bool
    {
        return $this->isFilterDialog() || $this->isFilterEntity();
    }

    /**
     * @phpstan-assert-if-true !null $this->dialog
     */
    private function isFilterDialog(): bool
    {
        return null !== $this->dialog;
    }

    /**
     * @phpstan-assert-if-true !null $this->entity
     */
    private function isFilterEntity(): bool
    {
        return null !== $this->entity;
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
        if ($this->isPrintable($height)) {
            $this->lineBreak(3);
        } else {
            $this->addPage();
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
     * @phpstan-param HelpDialogType|HelpEntityType $item
     */
    private function outputDescription(array $item): void
    {
        $description = $item['description'] ?? '';
        if ('' !== $description) {
            $this->multiCell(text: $description);
        }
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialog(array $dialog): void
    {
        $title = $this->getDialogTitle($dialog);
        if (!$this->isFilterDialog()) {
            $this->addBookmark($title, true, 2, false);
        }
        $this->outputTitle($title);
        $this->outputDescription($dialog);
        $this->outputDialogImage($dialog);
        $this->outputDialogImages($dialog);
        $this->outputDialogDetails($dialog);
        $this->outputDialogFields($dialog);
        $this->outputDialogEntityAndFields($dialog);
        $this->outputDialogEditActions($dialog);
        $this->outputDialogGlobalActions($dialog);
        $this->outputDialogForbidden($dialog);
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogDetails(array $dialog): void
    {
        $details = $dialog['details'] ?? [];
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
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogEditActions(array $dialog): void
    {
        $this->outputActions($dialog['editActions'] ?? [], 'help.labels.edit_actions');
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogEntityAndFields(array $dialog): void
    {
        $entity = $this->findEntity($dialog);
        $fields = $this->findFields($entity);
        if (null === $entity || null === $fields) {
            return;
        }
        if ($dialog['displayEntityColumns'] ?? false) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_columns');
            $this->outputEntityColumns($entity, $fields);
        }
        if ($dialog['displayEntityFields'] ?? false) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputEntityFields($entity, $fields);
        }
        if ($dialog['displayEntityActions'] ?? false) {
            $this->outputActions($entity['actions'] ?? [], 'help.labels.entity_actions');
        }
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogFields(array $dialog): void
    {
        $fields = $dialog['fields'] ?? [];
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
                $this->trans($field['name']),
                $field['description'],
            );
        }
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogForbidden(array $dialog): void
    {
        if (!isset($dialog['forbidden'])) {
            return;
        }
        $forbidden = $dialog['forbidden'];
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
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogGlobalActions(array $dialog): void
    {
        $this->outputActions($dialog['globalActions'] ?? [], 'help.labels.global_actions');
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogImage(array $dialog): void
    {
        if (!isset($dialog['image'])) {
            return;
        }
        $this->lineBreak(3);
        $this->outputText('help.labels.screenshot');
        $this->outputImage($dialog['image']);
    }

    /**
     * @phpstan-param HelpDialogType $dialog
     */
    private function outputDialogImages(array $dialog): void
    {
        $images = $dialog['images'] ?? [];
        if ([] === $images) {
            return;
        }
        foreach ($images as $image) {
            $this->lineBreak(3);
            $this->outputImage($image);
        }
    }

    private function outputDialogs(): void
    {
        $groupedDialogs = $this->filterDialogs();
        if ([] === $groupedDialogs) {
            return;
        }

        $pageAdded = false;
        $addBookmark = !$this->isFilterDialog();
        if ($addBookmark) {
            $this->addPage();
            $pageAdded = true;
            $title = $this->trans('help.dialog_menu');
            $this->addBookmark($title, true, 0, false);
            $this->outputTitle($title, 12);
            $this->outputLine();
        }

        foreach ($groupedDialogs as $group => $dialogs) {
            if ($addBookmark) {
                if (!$pageAdded) {
                    $this->addPage();
                    $pageAdded = true;
                }
                $this->addBookmark($group, true, 1, false);
            }
            foreach ($dialogs as $dialog) {
                if (!$pageAdded) {
                    $this->addPage();
                }
                $pageAdded = false;
                $this->outputDialog($dialog);
            }
        }
    }

    private function outputEntities(): void
    {
        $entities = $this->filterEntities();
        if ([] === $entities) {
            return;
        }

        $pageAdded = false;
        if (!$this->isFilterEntity()) {
            $this->addPage();
            $pageAdded = true;
            $title = $this->trans('help.entity_menu');
            $this->addBookmark($title, true, 0, false);
            $this->outputTitle($title, 12);
            $this->outputLine();
        }

        foreach ($entities as $entity) {
            if (!$pageAdded) {
                $this->addPage();
            }
            $pageAdded = false;
            $this->outputEntity($entity);
        }
    }

    /**
     * @phpstan-param HelpEntityType $entity
     */
    private function outputEntity(array $entity): void
    {
        $title = $this->getEntityTitle($entity);
        if (!$this->isFilterEntity()) {
            $this->addBookmark($title, true, 1, false);
        }
        $this->outputTitle($title);
        $this->outputDescription($entity);
        $fields = $this->findFields($entity);
        if (null !== $fields) {
            $this->lineBreak(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputEntityFields($entity, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }
        $this->outputEntityConstraints($entity);
        $this->outputEntityActions($entity);
    }

    /**
     * @phpstan-param HelpEntityType $entity
     */
    private function outputEntityActions(array $entity): void
    {
        $this->outputActions($entity['actions'] ?? [], 'help.labels.entity_actions');
    }

    /**
     * @phpstan-param HelpEntityType $entity
     * @phpstan-param HelpFieldType[] $fields
     */
    private function outputEntityColumns(array $entity, array $fields): void
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
                $this->formatFieldName($entity, $field),
                $field['description']
            );
        }
    }

    /**
     * @phpstan-param HelpEntityType $entity
     */
    private function outputEntityConstraints(array $entity): void
    {
        $constraints = $entity['constraints'] ?? [];
        if ([] === $constraints) {
            return;
        }

        $this->lineBreak(3);
        $this->outputText('help.labels.constraints');

        $margin = $this->getLeftMargin();
        $this->setLeftMargin($margin + 4.0);
        foreach ($constraints as $constraint) {
            $this->multiCell(text: \strip_tags('- ' . $constraint), align: PdfTextAlignment::LEFT);
        }
        $this->setLeftMargin($margin);
    }

    /**
     * @phpstan-param HelpEntityType $entity
     * @phpstan-param HelpFieldType[] $fields
     */
    private function outputEntityFields(array $entity, array $fields): void
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
                $this->formatFieldName($entity, $field),
                $field['description'],
                $this->formatFieldType($field),
                $this->formatRequired($field['required'] ?? true)
            );
        }
    }

    private function outputImage(string $image): void
    {
        $file = $this->service->getImageFile($image);
        if (!FileUtils::exists($file)) {
            return;
        }
        $width = $this->getImageSize($file)->width;
        if (0 === $width) {
            return;
        }
        $width = \min($this->pixels2UserUnit($width), $this->getPrintableWidth());
        $this->image(file: $file, width: $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        $this->defaultStyle->apply($this);
    }

    private function outputMainMenus(): void
    {
        if ($this->isFilter()) {
            return;
        }
        $menus = $this->service->getMainMenus();
        if ([] === $menus) {
            return;
        }

        $this->addPage();
        $title = $this->trans('help.main_menu');
        $this->addBookmark($title, true, 0, false);
        $this->outputTitle($title, 12);
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
    }

    /**
     * @phpstan-param HelpMenuType[] $menus
     */
    private function outputMenus(PdfTable $table, array $menus, int $indent = 0): void
    {
        $style = PdfStyle::getCellStyle()
            ->setIndent($indent);
        if (0 === $indent) {
            $style->setFontBold(true);
        }

        foreach ($menus as $menu) {
            /** @phpstan-var HelpMenuType $menu */
            $menu = $this->service->mergeAction($menu);
            $table->startRow()
                ->add($this->trans($menu['id']), style: $style)
                ->add($menu['description'] ?? null)
                ->endRow();
            /** @phpstan-var HelpMenuType[] $subMenus */
            $subMenus = $menu['menus'] ?? [];
            if ([] !== $subMenus) {
                $this->outputMenus($table, $subMenus, $indent + 4);
            }
        }
    }

    private function outputText(string $id, bool $translate = true): void
    {
        $this->multiCell(text: $translate ? $this->trans($id) : $id);
    }

    private function outputTitle(string $title, float $size = 10): void
    {
        $this->headerStyle->setFontSize($size)->apply($this);
        $this->outputText($title, false);
        $this->defaultStyle->apply($this);
    }
}
