<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * The goal of this form type is to check if an association is set
 * and mark entity as as "Required Update".
 * Also the association cannot be applied to the owning side entities.
 */
abstract class AbstractAssociationChoiceType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->isReadOnly($options)) {
            $this->disableView($view);
        }
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form    = $event->getForm();
        $options = $form->getConfig()->getOptions();
        /** @var EntityConfigId $configId */
        $configId = $options['config_id'];

        $newVal = $form->getData();
        $oldVal = $this->configManager->getConfig($configId)->get($form->getName());
        if ($this->isSchemaUpdateRequired($newVal, $oldVal)) {
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfig         = $extendConfigProvider->getConfig($configId->getClassName());
            if ($extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                $extendConfig->set('state', ExtendScope::STATE_UPDATED);

                $extendConfigProvider->persist($extendConfig);
                $extendConfigProvider->flush();
            }
        }
    }

    /**
     * Checks if the changes require the schema update
     *
     * @param mixed $newVal
     * @param mixed $oldVal
     *
     * @return bool
     */
    abstract protected function isSchemaUpdateRequired($newVal, $oldVal);

    /**
     * Check if the association choice element should be disabled or not
     *
     * For example it should be disabled if the editing entity is the owning side of association
     *
     * @param array $options
     *
     * @return bool
     */
    protected function isReadOnly($options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            // disable for dictionary entities
            $groupingConfigProvider = $this->configManager->getProvider('grouping');
            if ($groupingConfigProvider->hasConfig($className)) {
                $groups = $groupingConfigProvider->getConfig($className)->get('groups');
                if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups)) {
                    return true;
                }
            }
            // disable for immutable entities
            $configProvider = $this->configManager->getProvider($configId->getScope());
            if ($configProvider->hasConfig($className)) {
                $immutable = $configProvider->getConfig($className)->get('immutable');
                if (true === $immutable) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Disables the association choice element
     *
     * @param FormView $view
     */
    protected function disableView(FormView $view)
    {
        $view->vars['disabled'] = true;
    }

    /**
     * @param array  $vars
     * @param string $cssClass
     */
    protected function appendClassAttr(array &$vars, $cssClass)
    {
        if (isset($vars['attr']['class'])) {
            $vars['attr']['class'] .= ' ' . $cssClass;
        } else {
            $vars['attr']['class'] = $cssClass;
        }
    }
}