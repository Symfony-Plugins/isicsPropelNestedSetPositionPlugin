<?php
/*
 * This file is part of the isicsPropelNestedSetPositionPlugin package.
 * Copyright (c) 2008 ISICS.fr <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
/**
 * Propel nested set position widget
 *
 * @package isicsPropelNestedSetPositionPlugin
 * @author Nicolas CHARLOT <nicolas.charlot@isics.fr>
 **/
class isicsWidgetFormPropelNestedSetPosition extends sfWidgetForm
{
  
  /**
   * Constructor.
   *
   * Available options:
   *
   *  * node:       The nested set object (required)
   *  * connection: The Propel connection to use (null by default)
   *
   * @see sfWidgetFormSelect
   **/  
  protected function configure($options = array(), $attributes = array())
  {
    $this->addRequiredOption('node');
    $this->addOption('connection', null);
  }

  public function getMethodChoices()
  {
    $node = $this->getOption('node');
    
    if (!$node instanceof NodeObject)
    {
      throw new sfException('node param must be a node object');
    }    
    
    $i18n = sfContext::getInstance()->getI18N();
    $i18n_catalogue = sfConfig::get('isics_widget_form_propel_nested_set_position_i18n_catalogue');
    if ($node->isNew())
    {
      $method_choices = array(
        'insertAsFirstChildOf'  => $i18n->__('First child of', null, $i18n_catalogue),
        'insertAsLastChildOf'   => $i18n->__('Last child of', null, $i18n_catalogue),
        'insertAsPrevSiblingOf' => $i18n->__('Previous sibling of', null, $i18n_catalogue),
        'insertAsNextSiblingOf' => $i18n->__('Next sibling of', null, $i18n_catalogue),
        'insertAsParentOf'      => $i18n->__('Parent of', null, $i18n_catalogue)
      );
    }
    else
    {
      $method_choices = array(
        'moveToFirstChildOf'  => $i18n->__('First child of', null, $i18n_catalogue),
        'moveToLastChildOf'   => $i18n->__('Last child of', null, $i18n_catalogue),
        'moveToPrevSiblingOf' => $i18n->__('Previous sibling of', null, $i18n_catalogue),
        'moveToNextSiblingOf' => $i18n->__('Next sibling of', null, $i18n_catalogue)
      );
    }    
    
    return $method_choices;
  }

  /**
   * Returns the choices associated to the model.
   *
   * @return array An array of choices
   */
  public function getNodeChoices()
  {
    $choices  = array();
    
    $node = $this->getOption('node');
    
    if (!$node instanceof NodeObject)
    {
      throw new sfException('node param must be a node object');
    }    
    
    $tree = call_user_func(array($node->getPeer(), 'retrieveTree'), $node->getScopeIdValue(), $this->getOption('connection'));

    if ($tree)
    {
      $iterator = new RecursiveIteratorIterator($tree, RecursiveIteratorIterator::SELF_FIRST);
      foreach ($iterator as $node2)
      {
        $choices[$node2->getPrimaryKey()] = str_repeat('&middot;&nbsp;', $node2->getLevel()).$node2;
      }
    }

    return $choices;
  }
  
  /**
   * @param  string $name        The element name
   * @param  string $value       The value displayed in this widget
   * @param  array  $attributes  An array of HTML attributes to be merged with the default HTML attributes
   * @param  array  $errors      An array of errors for the field
   *
   * @see sfWidget
   **/  
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
  	$to_return = '';
  	
  	$node = $this->getOption('node');
  	
    if (!$node instanceof NodeObject)
    {
      throw new sfException('node param must be a node object');
    }  	
  	
  	$node_choices = $this->getNodeChoices();
    if ($node->isRoot() || empty($node_choices))
    {
    	$method_widget = new sfWidgetFormInputHidden();
    	$to_return  = $method_widget->render($name.'[method]', 'makeRoot');
    	$to_return .= sfContext::getInstance()->getI18N()->__('Root');
    }
    else
    {
	    if (is_null($value))
	    {    
	      if ($node->isNew())
	      {
	        $value = array(
	          'method'       => 'insertAsLastChildOf',
	          'related_node' => null
	        );
	      }      
	      else
	      {
	        $value = array();
	        if ($node->hasPrevSibling())
	        {
	          if ($node->hasNextSibling())
	          {
	            $value['method']       = 'moveToNextSiblingOf';
	            $value['related_node'] = $node->retrievePrevSibling()->getPrimaryKey();
	          }
	          else
	          {
	            $value['method']       = 'moveToLastChildOf';
	            $value['related_node'] = $node->retrieveParent()->getPrimaryKey();
	          }
	        }
	        else
	        {
	          $value['method']       = 'moveToFirstChildOf';
	          $value['related_node'] = $node->retrieveParent()->getPrimaryKey();
	        }        
	      } 
	    }
	    
	    // Method widget
	    $method_widget = new sfWidgetFormSelect(array('choices' => ($this->getMethodChoices())));
	    $to_return     = $method_widget->render($name.'[method]', $value['method'], $attributes);
	    
	    // Node widget
	    $node_widget = new sfWidgetFormSelect(array('choices' => $node_choices));
	    $to_return  .= $node_widget->render($name.'[related_node]', $value['related_node'], $attributes);	    
    }
    
    return $to_return;
  }
  
}