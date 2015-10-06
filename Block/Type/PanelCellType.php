<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\GluonBundle\Block\Type;

use Sonatra\Bundle\BlockBundle\Block\AbstractType;
use Sonatra\Bundle\BlockBundle\Block\BlockBuilderInterface;
use Sonatra\Bundle\BlockBundle\Block\BlockInterface;
use Sonatra\Bundle\BlockBundle\Block\BlockView;
use Sonatra\Bundle\BlockBundle\Block\Exception\InvalidConfigurationException;
use Sonatra\Bundle\BlockBundle\Block\Util\BlockUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Panel Cell Block Type.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class PanelCellType extends AbstractType
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * Constructor.
     *
     * @param PropertyAccessorInterface $propertyAccessor The property accessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
        if (null !== $options['help']) {
            $hOpts = array_replace($options['options'], array(
                'label' => '?',
                'style' => 'info',
                'size' => 'xs',
                'attr' => array('class' => 'panel-cell-help'),
                'popover' => $options['help'],
            ));

            $builder->add($builder->getName().'_help', 'button', $hOpts);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addParent(BlockInterface $parent, BlockInterface $block, array $options)
    {
        if (!BlockUtil::isValidBlock(array('panel_section', 'panel_row'), $parent)) {
            $msg = 'The "panel_cell" parent block (name: "%s") must be a "panel_section" block type';
            throw new InvalidConfigurationException(sprintf($msg, $block->getName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if ($options['property_path'] && (is_object($block->getData()) || is_array($block->getData()))) {
            $value = $this->propertyAccessor->getValue($block->getData(), $options['property_path']);

            $view->vars = array_replace($view->vars, array(
                'data' => $value,
                'value' => $value,
            ));
        }

        $labelAttr = $view->vars['label_attr'];
        $class = isset($labelAttr['class']) ? $labelAttr['class'] : '';
        $class = trim('control-label '.$class);

        if (null !== $options['label_style']) {
            $class .= ' control-label-'.$options['label_style'];
        }

        $labelAttr['class'] = trim($class);

        $view->vars = array_replace($view->vars, array(
            'control_attr' => $options['control_attr'],
            'layout_col_size' => $options['layout_size'],
            'layout_col_width' => $options['layout'],
            'layout_col_max' => $options['layout_max'],
            'label_style' => $options['label_style'],
            'label_attr' => $labelAttr,
            'rendered' => $options['rendered'],
            'hidden' => $options['hidden'],
            'value_type' => $options['type'],
            'value_options' => $options['options'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach ($view->children as $name => $child) {
            if (in_array('button', $child->vars['block_prefixes'])) {
                $view->vars['button_help'] = $child;
                unset($view->children[$name]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'inherit_data' => function (Options $options) {
                return null !== $options['property_path'];
            },
            'type' => null,
            'options' => array(),
            'control_attr' => array(),
            'layout_size' => 'sm',
            'layout' => 12,
            'layout_max' => 12,
            'label_style' => null,
            'rendered' => true,
            'hidden' => false,
            'help' => null,
        ));

        $resolver->addAllowedTypes('type', array('null', 'string', 'Sonatra\Bundle\BlockBundle\Block\BlockTypeInterface'));
        $resolver->addAllowedTypes('options', 'array');
        $resolver->addAllowedTypes('control_attr', 'array');
        $resolver->addAllowedTypes('layout_size', 'string');
        $resolver->addAllowedTypes('layout', 'int');
        $resolver->addAllowedTypes('layout_max', 'int');
        $resolver->addAllowedTypes('label_style', array('null', 'string'));
        $resolver->addAllowedTypes('rendered', 'bool');
        $resolver->addAllowedTypes('hidden', 'bool');
        $resolver->addAllowedTypes('help', array('null', 'string', 'array'));

        $resolver->addAllowedValues('layout_size', array('sm', 'md', 'lg'));
        $resolver->addAllowedValues('label_style', array(
            null,
            'default',
            'primary',
            'accent',
            'success',
            'info',
            'warning',
            'danger',
        ));

        $resolver->setNormalizer('layout', function (Options $options, $value) {
            $value = max($value, 1);
            $value = min($value, $options['layout_max']);

            return $value;
        });
        $resolver->setNormalizer('help', function (Options $options, $value) {
            if (null === $value) {
                return $value;
            } elseif (is_string($value)) {
                $value = array(
                    'content' => $value,
                );
            }

            $value = array_replace(array(
                'html' => true,
                'placement' => 'auto top',
            ), $value);

            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'panel_cell';
    }
}
