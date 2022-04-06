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

use App\Controller\AbstractController;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\HelpService;
use App\Util\FileUtils;

/**
 * Report for the help documentation.
 *
 * @author Laurent Muller
 */
class HelpReport extends AbstractReport
{
    /**
     * The absolute path to the images.
     */
    private readonly string $imagePath;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param HelpService        $service    the help service
     */
    public function __construct(AbstractController $controller, private readonly HelpService $service)
    {
        parent::__construct($controller);
        $this->imagePath = $service->getImagePath();
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        /**
         * @psalm-var array<array{
         *      id: string,
         *      description: string,
         *      menus: array|null
         *      }> $mainMenus
         */
        $mainMenus = $this->service->getMainMenus();

        /**
         * @psalm-var array<array{
         *      id: string,
         *      description: string|null,
         *      image: string|null,
         *      displayEntityColumns: null|bool,
         *      displayEntityFields: null|bool,
         *      displayEntityActions: null|bool,
         *      entity: null|string,
         *      editActions: null|array,
         *      globalActions: null|array,
         *      forbidden: null|array,
         *      details: string[]|null}> $dialogs
         */
        $dialogs = $this->service->getDialogs();

        /**
         * @psalm-var array<array{
         *      id: string,
         *      name: string,
         *      description: string|null,
         *      constraints: string[]|null,
         *      actions: array|null,
         *      fields: array|null,
         *      required: bool|null}> $entities
         */
        $entities = $this->service->getEntities();

        if (empty($mainMenus) && empty($dialogs) && empty($entities)) {
            return false;
        }

        // title
        $this->setTitleTrans('help.title');

        // content
        $newPage = $this->outputMainMenus($mainMenus, true);
        $newPage = $this->outputDialogs($dialogs, $newPage);
        $this->outputEntities($entities, $newPage);

        return true;
    }

    /**
     * Convert the BR tags to the end of line symbol of this platform.
     */
    private function br2nl(string $str): string
    {
        return \preg_replace('/\<br(\s*)?\/?\>/i', \PHP_EOL, $str);
    }

    /**
     * @psalm-param array{entity: null|string} $dialog
     * @psalm-return null|array{
     *      id: string,
     *      name:string,
     *      description: string,
     *      fields: null|array,
     *      actions: null|array,
     *      editActions: null|array}
     */
    private function findEntity(array $dialog): ?array
    {
        $id = $dialog['entity'] ?? null;
        if (null !== $id) {
            /** @psalm-var null|array{
             *      id: string,
             *      name:string,
             *      description: string,
             *      fields: null|array,
             *      actions: null|array,
             *      editActions: null|array} $entity */
            $entity = $this->service->findEntity($id);
            if (null !== $entity) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @psalm-param array{fields: null|array} $entity
     * @psalm-return null|array<array{
     *      name: string,
     *      description: string,
     *      type: string|null,
     *      length: int|null,
     *      required: bool|null}>
     */
    private function findFields(?array $entity): ?array
    {
        if (null !== $entity) {
            /** @psalm-var null|array<array{
             *      name: string,
             *      description: string,
             *      type: string|null,
             *      length: int|null,
             *      required: bool|null}> $fields */
            $fields = $entity['fields'] ?? null;
            if (null !== $fields) {
                return $fields;
            }
        }

        return null;
    }

    /**
     * @psalm-param array{id: string} $item
     * @psalm-param array{name: string} $field
     */
    private function formatFieldName(array $item, array $field): string
    {
        $id = $item['id'];
        $name = $field['name'];

        return $this->trans("$id.fields.$name");
    }

    /**
     * @psalm-param array{
     *      type: string|null,
     *      length: int|null} $field
     */
    private function formatFieldType(array $field): string
    {
        $default = $field['type'] ?? 'text';
        $type = $this->trans("help.types.$default");
        if ($length = $field['length'] ?? null) {
            return "$type ($length)";
        }

        return $type;
    }

    private function formatRequired(bool $required): string
    {
        return $this->trans($required ? 'common.value_true' : 'common.value_false');
    }

    /**
     * @psalm-param array<array{id: string, description: string}> $actions
     */
    private function outputActions(array $actions, string $description): void
    {
        $this->Ln(3);
        $this->outputText($description);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.action'), 70, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->outputHeaders();

        foreach ($actions as $action) {
            $table->startRow()
                ->add($this->trans($action['id']))
                ->add($action['description'])
                ->endRow();
        }
    }

    /**
     * @psalm-param array{id: string} $item
     * @psalm-param array<array{name: string, description: string}> $fields
     */
    private function outputColumns(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.column'), 30, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->outputHeaders();
        foreach ($fields as $field) {
            $table->startRow()
                ->add($this->formatFieldName($item, $field))
                ->add($field['description'])
                ->endRow();
        }
    }

    /**
     * @psalm-param string[] $constraints
     */
    private function outputConstraints(array $constraints): void
    {
        $margin = $this->getLeftMargin();
        $this->SetLeftMargin($margin + 4);
        foreach ($constraints as $constraint) {
            $this->MultiCell(0, self::LINE_HEIGHT, \strip_tags("- $constraint"), PdfBorder::none(), PdfTextAlignment::LEFT);
        }
        $this->SetLeftMargin($margin);
    }

    /**
     * @psalm-param string[] $details
     */
    private function outputDetails(array $details): void
    {
        $text = \array_reduce($details, function (string $carry, string $str): string {
            $str = \strip_tags($this->br2nl($str));

            return empty($carry) ? $str : $carry . ' ' . $str;
        }, '');
        $this->MultiCell(0, self::LINE_HEIGHT, $text, PdfBorder::none(), PdfTextAlignment::LEFT);
    }

    /**
     * @psalm-param array{
     *      id: string,
     *      description: string|null,
     *      image: string|null,
     *      displayEntityColumns: null|bool,
     *      displayEntityFields: null|bool,
     *      displayEntityActions: null|bool,
     *      entity: null|string,
     *      editActions: null|array,
     *      globalActions: null|array,
     *      forbidden: null|array,
     *      details: string[]|null} $item
     */
    private function outputDialog(array $item): void
    {
        // title
        $this->outputTitle($item['id']);

        // description
        if ($description = $item['description'] ?? false) {
            $this->MultiCell(0, self::LINE_HEIGHT, $description);
        }

        // image
        if ($image = $item['image'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.screenshot');
            $this->outputImage($image);
        }

        // details
        if ($details = $item['details'] ?? null) {
            $this->Ln(3);
            $this->outputText('help.labels.description');
            $this->outputDetails($details);
        }

        // entity and fields
        $entity = $this->findEntity($item);
        $fields = $this->findFields($entity);
        if (null !== $entity && null !== $fields) {
            // columns
            if (isset($item['displayEntityColumns']) && $item['displayEntityColumns']) {
                $this->Ln(3);
                $this->outputText('help.labels.edit_columns');
                $this->outputColumns($entity, $fields);
            }

            // fields
            if (isset($item['displayEntityFields']) && $item['displayEntityFields']) {
                $this->Ln(3);
                $this->outputText('help.labels.edit_fields');
                $this->outputFields($entity, $fields);
            }

            // actions
            $displayEntityActions = $item['displayEntityActions'] ?? false;
            if ($displayEntityActions) {
                /** @var array<array{id: string, description: string}>|null $actions */
                $actions = $entity['actions'] ?? null;
                if (null !== $actions) {
                    $this->outputActions($actions, 'help.labels.entity_actions');
                }
            }
        }

        // edit actions
        /** @psalm-var null|array<array{
         *      id: string,
         *      description: string}> $actions */
        $actions = $item['editActions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.edit_actions');
        }

        // global actions
        /** @psalm-var null|array<array{
         *      id: string,
         *      description: string}> $actions */
        $actions = $item['globalActions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.global_actions');
        }

        /**
         * @psalm-var null|array{
         *      image: string|null,
         *      text:string|null,
         *      action: array|null} $forbidden
         */
        $forbidden = $item['forbidden'] ?? null;
        if (null !== $forbidden) {
            $this->Ln(3);
            $text = $forbidden['text'] ?? $this->trans('help.labels.forbidden_text');
            $this->outputText($text, false);
            $image = $forbidden['image'] ?? null;
            if (null !== $image) {
                $this->outputImage($image);
            }
            /** @psalm-var null|array{id: string, description: string} $action */
            $action = $forbidden['action'] ?? null;
            if (null !== $action) {
                $this->outputActions([$action], 'help.labels.edit_actions');
            }
        }
    }

    /**
     * @psalm-param array<array{
     *      id: string,
     *      description: string|null,
     *      image: string|null,
     *      displayEntityColumns: null|bool,
     *      displayEntityFields: null|bool,
     *      displayEntityActions: null|bool,
     *      entity: null|string,
     *      editActions: null|array,
     *      globalActions: null|array,
     *      forbidden: null|array,
     *      details: string[]|null}> $dialogs
     */
    private function outputDialogs(array $dialogs, bool $newPage): bool
    {
        if (empty($dialogs)) {
            return false;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $this->outputTitle('help.dialog_menu', 12);
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
     * @psalm-param array<array{
     *      id: string,
     *      name: string,
     *      description: string|null,
     *      constraints: string[]|null,
     *      actions: array|null,
     *      fields: array|null,
     *      required: bool|null}> $entities
     */
    private function outputEntities(array $entities, bool $newPage): bool
    {
        if (empty($entities)) {
            return false;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $this->outputTitle('help.entity_menu', 12);
        $this->outputLine();

        foreach ($entities as $entity) {
            if ($newPage) {
                $this->AddPage();
            }
            $newPage = true;
            $this->outputEntity($entity);
        }

        return true;
    }

    /**
     * @psalm-param array{
     *      id: string,
     *      name: string,
     *      description: string|null,
     *      constraints: string[]|null,
     *      actions: array|null,
     *      fields: array|null,
     *      required: bool|null} $item
     */
    private function outputEntity(array $item): void
    {
        $this->outputTitle($item['id'] . '.name');

        if ($description = $item['description'] ?? false) {
            $this->MultiCell(0, self::LINE_HEIGHT, $description);
        }

        $fields = $this->findFields($item);
        if (null !== $fields) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($item, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }

        $constraints = $item['constraints'] ?? null;
        if (null !== $constraints) {
            $this->Ln(3);
            $this->outputText('help.labels.constraints');
            $this->outputConstraints($constraints);
        }

        /** @psalm-var null|array<array{id: string, description: string}> $actions */
        $actions = $item['actions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.entity_actions');
        }
    }

    /**
     * @psalm-param array{
     *      id: string,
     *      name:string,
     *      description: string|null} $item
     *
     * @psalm-param array<array{
     *      name: string,
     *      description: string,
     *      type: string|null,
     *      length: int|null,
     *      required: bool|null}> $fields
     */
    private function outputFields(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.field'), 30, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->addColumn(PdfColumn::left($this->trans('help.fields.type'), 30, true))
            ->addColumn(PdfColumn::center($this->trans('help.fields.required'), 18, true))
            ->outputHeaders();
        foreach ($fields as $field) {
            $table->startRow()
                ->add($this->formatFieldName($item, $field))
                ->add($field['description'])
                ->add($this->formatFieldType($field))
                ->add($this->formatRequired($field['required'] ?? true))
                ->endRow();
        }
    }

    private function outputImage(string $image): void
    {
        $file = $this->imagePath . $image;
        if (!FileUtils::exists($file)) {
            return;
        }

        /** @var float[] $size */
        $size = \getimagesize($file);
        $width = $this->pixels2UserUnit($size[0]);
        $width = \min($width, $this->getPrintableWidth());
        $this->Image($file, null, null, $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        PdfStyle::getDefaultStyle()->apply($this);
    }

    /**
     * @psalm-param array<array{
     *      id: string,
     *      description: string,
     *      menus: array|null}> $menus
     */
    private function outputMainMenus(array $menus, bool $newPage): bool
    {
        if (!empty($menus)) {
            if ($newPage) {
                $this->AddPage();
            }

            $this->outputTitle('help.main_menu', 12);
            $this->outputLine();

            /**
             * @psalm-param null|array{description: string|null, image: string|null}  $rootMenu
             */
            if ($rootMenu = $this->service->getMainMenu()) {
                // description
                /** @psalm-var string|null $description */
                $description = $rootMenu['description'] ?? null;
                if (null !== $description) {
                    $this->outputText($description, false);
                }

                // image
                /** @psalm-var string|null $image */
                $image = $rootMenu['image'] ?? null;
                if (null !== $image) {
                    $this->Ln(3);
                    $this->outputText('help.labels.screenshot');
                    $this->outputImage($image);
                }
            }

            // menus
            $this->Ln(3);
            $this->outputText('help.labels.edit_actions');
            $table = new PdfTableBuilder($this);
            $table->addColumn(PdfColumn::left($this->trans('help.fields.action'), 60, true))
                ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
                ->outputHeaders();
            $this->outputMenus($table, $menus);

            return true;
        }

        return false;
    }

    /**
     * @psalm-param array<array{
     *      id: string,
     *      description: string,
     *      menus: array|null
     *      }> $menus
     *  @psalm-suppress MixedArgumentTypeCoercion
     */
    private function outputMenus(PdfTableBuilder $table, array $menus, int $indent = 0): void
    {
        $style = PdfStyle::getCellStyle()->setIndent($indent);
        foreach ($menus as $menu) {
            $table->startRow()
                ->add($this->splitTrans($menu['id']), 1, $style)
                ->add($menu['description'])
                ->endRow();

            /**
             * @psalm-var null|array $children.
             */
            $children = $menu['menus'] ?? null;
            if (null !== $children) {
                $this->outputMenus($table, $children, $indent + 4);
            }
        }
    }

    private function outputText(string $id, bool $translate = true): void
    {
        if ($translate) {
            $id = $this->trans($id);
        }
        $this->MultiCell(0, self::LINE_HEIGHT, $id);
    }

    private function outputTitle(string $id, float $size = 10): void
    {
        PdfStyle::getHeaderStyle()->setFontSize($size)->apply($this);
        $this->outputText($id);
        PdfStyle::getDefaultStyle()->apply($this);
    }

    private function splitTrans(string $id): string
    {
        $values = \explode('|', $id);
        if (2 === \count($values)) {
            return $this->trans($values[0], [], $values[1]);
        }

        return $this->trans($id);
    }
}
