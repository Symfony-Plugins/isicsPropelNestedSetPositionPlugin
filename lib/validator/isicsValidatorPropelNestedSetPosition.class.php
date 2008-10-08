<?php
/*
 * This file is part of the isicsPropelNestedSetPositionPlugin package.
 * Copyright (c) 2008 ISICS.fr <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Propel nested set position validator
 *
 * @package isicsPropelNestedSetPositionPlugin
 * @author Nicolas CHARLOT <nicolas.charlot@isics.fr>
 **/
class isicsValidatorPropelNestedSetPosition extends sfValidatorBase
{
  /**
   * Insert methods
   *
   * @var array insert methods
   **/
  protected static $insertMethods = array(
    'insertAsFirstChildOf',
    'insertAsLastChildOf',
    'insertAsPrevSiblingOf',
    'insertAsNextSiblingOf',
    'insertAsParentOf'
  );
  
  /**
   * Move methods
   *
   * @var array move methods
   **/
  protected static $moveMethods = array(
    'moveToFirstChildOf',
    'moveToLastChildOf',
    'moveToPrevSiblingOf',
    'moveToNextSiblingOf'
  );
  
  /**
   * Configures the current validator.
   *
   * Available options:
   *
   *  * model:              The model class (required)
   *  * max_depth:          Maximum depth authorized (null by default)
   *  * connection:         The Propel connection to use (null by default)
   *
   * @see sfValidatorBase
   */  
  protected function configure($options = array(), $messages = array())
  {
    $this->addRequiredOption('node');
    $this->addOption('max_depth', null);
    $this->addOption('connection', null);
  }
  
  /**
   * @see sfValidatorBase
   */
  protected function doClean($value)
  {
  	$node = $this->getOption('node');
  	
  	if (!$node instanceof NodeObject)
  	{
  		throw new sfException('node param must be a node object');
  	}
  	
  	if ($value['method'] == 'makeRoot')
  	{
  		if ($node->isNew())
  		{
  		  $root_node = call_user_func(array($node->getPeer(), 'retrieveRoot'), $node->getScopeIdValue(), $this->getOption('connection'));
  		  if (!is_null($root_node))
  			{
  				throw new sfValidatorError($this, 'root node already exists', array('value' => $value));
  			}
  			
  		  return $value;
  		}
  		else if (!$node->isRoot())
  		{
  			throw new sfValidatorError($this, 'root node already exists', array('value' => $value));
  		}
  		else
  		{
  		  return null;
  		}
  	}

    $criteria = new Criteria();
    $criteria->add($this->getPrimaryKey(), $value['related_node']);
    $related_node = call_user_func(array($node->getPeer(), 'doSelectOne'), $criteria, $this->getOption('connection'));

    if (is_null($related_node))
    {
      throw new sfValidatorError($this, 'invalid node', array('value' => $value));
    }
    
    if ($related_node->getScopeIdValue() != $node->getScopeIdValue())
    {
    	throw new sfValidatorError($this, 'invalid scope', array('value' => $value));
    }
    
    foreach ($related_node->getPath() as $path_item)
    {
    	if ($path_item->isEqualTo($node))
    	{
    		throw new sfValidatorError($this, 'invalid position', array('value' => $value));
    	}
    }
    
    if ($node->isNew() && !in_array($value['method'], self::$insertMethods)
      || !$node->isNew() && !in_array($value['method'], self::$moveMethods))
    {
      throw new sfValidatorError($this, 'invalid method', array('value' => $value));
    }
    
    if (substr($value['method'], -7) == 'ChildOf')
    {
      if (!is_null($this->getOption('max_depth')) && $related_node->getLevel() >= $this->getOption('max_depth'))
      {
        throw new sfValidatorError($this, 'invalid depth: max depth is '.$this->getOption('max_depth'), array('value' => $value));
      }      
    }
    else
    {
      if ($related_node->isRoot())
      {
        throw new sfValidatorError($this, 'invalid position', array('value' => $value));
      }
      if (!is_null($this->getOption('max_depth')) && $related_node->getLevel() > $this->getOption('max_depth'))
      {
        throw new sfValidatorError($this, 'invalid depth: max depth is '.$this->getOption('max_depth'), array('value' => $value));
      }
    }
    
    $value['related_node'] = $related_node;
    
    return $value;
  }  

  /**
   * Returns the primary key to use for comparison.
   *
   * @return string The primary key name
   */
  protected function getPrimaryKey()
  {
  	$node = $this->getOption('node');
    $map = call_user_func(array($node->getPeer(), 'getTableMap'));
    foreach ($map->getColumns() as $column)
    {
      if ($column->isPrimaryKey())
      {
        $columnName = strtolower($column->getColumnName());
        break;
      }
    }
    
    return call_user_func(array($node->getPeer(), 'translateFieldName'), $columnName, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_COLNAME);
  }

}