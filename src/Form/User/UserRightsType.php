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

namespace App\Form\User;

use App\Entity\User;
use App\Form\FormHelper;
use App\Util\Utils;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User rights type.
 *
 * @author Laurent Muller
 */
class UserRightsType extends RightsType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, RoleHierarchyInterface $roleHierarchy, TranslatorInterface $translator)
    {
        parent::__construct($kernel, $roleHierarchy);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * Translate the enabled state.
     *
     * @param string $enabled the enabled state
     *
     * @return string the translated enabled state
     */
    public function translateEnabled(string $enabled): string
    {
        $enabled = \filter_var($enabled, \FILTER_VALIDATE_BOOLEAN);

        return $this->translator->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * Translate the given role.
     *
     * @param string $role the role name
     *
     * @return string the translated role
     */
    public function translateRole(string $role): string
    {
        return Utils::translateRole($this->translator, $role);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);

        $helper->field('username')
            ->addPlainType(true);

        $helper->field('role')
            ->updateOption('transformer', [$this, 'translateRole'])
            ->addPlainType(true);

        $helper->field('enabled')
            ->updateOption('transformer', [$this, 'translateEnabled'])
            ->addPlainType(true);

        $helper->field('overwrite')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
