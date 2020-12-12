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

namespace App\Form\Admin;

use App\Form\FormHelper;
use App\Form\Type\MinStrengthType;
use App\Interfaces\ActionInterface;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Type for application parameters.
 *
 * @author Laurent Muller
 */
class ParametersType extends AbstractType implements ApplicationServiceInterface
{
    /**
     * The password options.
     */
    public const PASSWORD_OPTIONS = [
        'letters',
        'numbers',
        'specialchar',
        'casediff',
        'email',
        'pwned',
    ];

    /**
     * @var ApplicationService
     */
    private $application;

    /**
     * @var bool
     */
    private $superAdmin = false;

    /**
     * Constructor.
     */
    public function __construct(Security $security, ApplicationService $application)
    {
        $this->application = $application;
        if ($user = $security->getUser()) {
            $this->superAdmin = $user instanceof RoleInterface && $user->isSuperAdmin();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder, 'parameters.fields.');

        // customer
        $this->addCustomerSection($helper);

        // default values
        $this->addDefaultValueSection($helper);

        // flashbag
        $this->addFlashbagSection($helper);

        // security
        if ($this->superAdmin) {
            $this->addSecuritySection($helper);
        }
    }

    private function addCustomerSection(FormHelper $helper): void
    {
        $helper->field(self::P_CUSTOMER_NAME)
            ->updateRowAttribute('class', 'ml-2')
            ->addTextType();

        $helper->field(self::P_CUSTOMER_URL)
            ->updateRowAttribute('class', 'ml-2')
            ->addUrlType();
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(self::P_DEFAULT_STATE)
            ->addStateType();

        $helper->field(self::P_DEFAULT_CATEGORY)
            ->addCategoryType();

        $helper->field(self::P_MIN_MARGIN)
            ->updateAttribute('data-default', self::DEFAULT_MIN_MARGIN * 100)
            ->percent(true)
            ->addPercentType(0);

        $helper->field(self::P_DISPLAY_TABULAR)
            ->updateAttribute('data-default', (int) self::DEFAULT_TABULAR)
            ->addChoiceType([
                'parameters.tabular.table' => true,
                'parameters.tabular.flex' => false,
            ]);

        $helper->field(self::P_EDIT_ACTION)
            ->updateAttribute('data-default', self::DEFAULT_ACTION)
            ->addChoiceType([
                'parameters.editAction.show' => ActionInterface::ACTION_SHOW,
                'parameters.editAction.edit' => ActionInterface::ACTION_EDIT,
                'parameters.editAction.none' => ActionInterface::ACTION_NONE,
            ]);
    }

    private function addFlashbagSection(FormHelper $helper): void
    {
        $helper->field(self::P_MESSAGE_POSITION)
            ->updateAttribute('data-default', self::DEFAULT_POSITION)
            ->addChoiceType($this->getPositions());

        $helper->field(self::P_MESSAGE_TIMEOUT)
            ->updateAttribute('data-default', self::DEFAULT_TIMEOUT)
            ->addChoiceType($this->getTimeouts());

        $helper->field(self::P_MESSAGE_SUB_TITLE)
            ->updateAttribute('data-default', (int) self::DEFAULT_SUB_TITLE)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        // security
        $captcha = (int) !$this->application->getDebug();
        $helper->field(self::P_DISPLAY_CAPTCHA)
            ->updateAttribute('data-default', $captcha)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);

        $helper->field(self::P_MIN_STRENGTH)
            ->label('password.minstrength')
            ->updateAttribute('data-default', -1)
            ->add(MinStrengthType::class);

        // password options
        foreach (self::PASSWORD_OPTIONS as $option) {
            $helper->field($option)
                ->label("password.{$option}")
                ->updateAttribute('data-default', 0)
                ->updateRowAttribute('class', 'mb-1 ml-1')
                ->notRequired()
                ->addCheckboxType();
        }
    }

    /**
     * Gets the message position choices.
     */
    private function getPositions(): array
    {
        $entries = [
            'top-left',
            'top-center',
            'top-right',
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];

        $result = [];
        foreach ($entries as $entry) {
            $result['parameters.message_position.' . $entry] = $entry;
        }

        return $result;
    }

    /**
     * Gets the message timeout choices.
     */
    private function getTimeouts(): array
    {
        $result = [];
        for ($i = 1; $i < 6; ++$i) {
            $result["parameters.message_timeout.{$i}"] = $i * 1000;
        }

        return  $result;
    }
}
