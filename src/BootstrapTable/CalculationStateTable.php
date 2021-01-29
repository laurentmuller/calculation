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

namespace App\BootstrapTable;

use App\Repository\CalculationStateRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The calculation states table.
 *
 * @author Laurent Muller
 */
class CalculationStateTable extends AbstractEntityTable
{
    private TranslatorInterface $translator;

    /**
     * Constructor.
     */
    public function __construct(CalculationStateRepository $repository, TranslatorInterface $translator)
    {
        parent::__construct($repository);
        $this->translator = $translator;
    }

    public function formatEditable(bool $value): string
    {
        if ($value) {
            return $this->translator->trans('common.value_true');
        }

        return $this->translator->trans('common.value_false');
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/calculation_state.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => Column::SORT_ASC];
    }
}
