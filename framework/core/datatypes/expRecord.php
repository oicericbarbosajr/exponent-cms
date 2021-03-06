<?php
/**
 * This file is part of Exponent Content Management System
 *
 * Exponent is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * @category   Exponent CMS
 * @package    Framework
 * @subpackage Datatypes
 * @author     Adam Kessler <adam@oicgroup.net>
 * @copyright  2004-2009 OIC Group, Inc.
 * @license    GPL: http://www.gnu.org/licenses/gpl.txt
 * @version    Release: @package_version@
 * @link       http://www.exponent-docs.org/api/package/PackageName
 */
 
class expRecord {
    protected $classinfo = null;
    public $tablename = '';
    public $classname = '';
    public $identifier = 'id'; 

    public $has_extended_fields = array();
    public $has_one = array();
    public $has_many = array();
    public $has_many_self = array(); 
    public $has_and_belongs_to_many = array();
    public $has_and_belongs_to_self = array();
    
    public $get_assoc_for = array(); 
    public $get_attachable_for = array(); 
    
    public $default_sort_field = '';
    public $default_sort_direction = '';
    
    public $validate = array();
    public $do_not_validate = array();
    
    public $supports_revisions = false;

    //chnaged to nothing by default to speed things up. FJD
    protected $attachable_item_types = array(); 
   /* protected $attachable_item_types = array(
        'content_expFiles'=>'expFile', 
        'content_expTags'=>'expTag', 
        'content_expComments'=>'expComment',
        'content_expSimpleNote'=>'expSimpleNote',
        //'content_expEventdate'=>'expEventdate', 
    );*/

    function __construct($params=null, $get_assoc=true, $get_attached=true) {
        global $db;
        
        // @TODO Change this conditional check as the default value in arguement list 
        // if the params come thru as a null value we need to convert to an empty array
        if (empty($params)) {
            $params = array();
            $get_assoc = false;
            $get_attached = false;
        }

        // figure out the basic table info about this model
        $this->classinfo = new ReflectionClass($this);
        $this->classname = $this->classinfo->getName();
        $this->tablename = isset($this->table) ? $this->table : $this->classinfo->getName();        

        // if the user passed in arguments to this constructor then we need to
        // retrieve objects 

        // If a number was sent in, we assume this is a DB record ID, so pull it
        if (!is_object($params) && !is_array($params)) {
            $where = '';
            if (is_numeric($params)) {          
                $this->build($db->selectArray($this->tablename, $this->identifier .'='.$params, $this->supports_revisions));                    
                $identifier = $this->identifier;
                $params = array($identifier=>$params); // Convert $params (given number value) into an key/value pair        
            } else {
                // try to look up by sef_url
                $values = $db->selectArray($this->tablename, "sef_url='".$params."'", $this->supports_revisions);
                // if we didn't find it via sef_url then we should check by title
                if (empty($values)) $values = $db->selectArray($this->tablename, "title='".$params."'", $this->supports_revisions);
                $this->build($values);
                $params = array('title'=>$params);
            }
            
        } else {
            // Otherwise we assume that in inbound is an array or Object to be processed as is.        
            $this->build($params);
        }

        // setup the exception array if it's not there.  This array tells the getAssociatedObjectsForThisModel() function which 
        // modules NOT to setup.  This stops us from getting infinant loops with many to many relationships.
        $params['except'] = isset($params['except']) ? $params['except'] : array();
        //if (!empty($this->id)) $this->getAssociatedObjectsForThisModel($params['except']);
        if ($get_assoc) $this->getAssociatedObjectsForThisModel($params['except']);
        if ($get_attached) $this->getAttachableItems();        
    }

    public function find($range='all', $where=null, $order=null, $limit=null, $limitstart=0, $get_assoc=true, $get_attached=true) {
        global $db;

        if (is_numeric($range)) {
            $where = $this->identifier.'='.intval($range); // If we hit this then we are expecting just a simple id 
            $range = 'first';
        } 

        $sql  = empty($where) ? 1 : $where;
        //eDebug("Supports Revisions:" . $this->supports_revisions);
        if ($this->supports_revisions && $range != 'revisions') $sql .= " AND revision_id=(SELECT MAX(revision_id) FROM `" . $db->prefix .$this->tablename."` WHERE $where)";
        $sql .= empty($order) ? '' : ' ORDER BY '.$order;

        if (strcasecmp($range, 'all') == 0 || strcasecmp($range, 'revisions') == 0) {
            $sql .= empty($limit) ? '' : ' LIMIT '.$limitstart.','.$limit;
            return $db->selectExpObjects($this->tablename, $sql, $this->classname, $get_assoc, $get_attached);
        } elseif (strcasecmp($range, 'first') == 0) {   
            $sql .= ' LIMIT 0,1';
            $records = $db->selectExpObjects($this->tablename, $sql, $this->classname, $get_assoc, $get_attached);
            return empty($records) ? null : $records[0];  
        } elseif (strcasecmp($range, 'bytitle') == 0) {
            $records = $db->selectExpObjects($this->tablename, "title='".$where."' OR sef_url='".$where."'", $this->classname, $get_assoc, $get_attached);
            return empty($records) ? null : $records[0];
        } elseif (strcasecmp($range, 'count') == 0) {
            return $db->countObjects($this->tablename, $sql);
        } elseif (strcasecmp($range, 'in') == 0) {
            if (!is_array($where)) return array();
            foreach ($where as $id) $records[] = new $this->classname($id);
            return $records;
        } elseif (strcasecmp($range, 'bytag') == 0) {
            $sql  = 'SELECT DISTINCT m.id FROM '.DB_TABLE_PREFIX.'_'.$this->table.' m ';
            $sql .= 'JOIN '.DB_TABLE_PREFIX.'_content_expTags ct '; 
            $sql .= 'ON m.id = ct.content_id WHERE ct.exptag_id='.$where." AND ct.content_type='".$this->classname."'";
            if ($this->supports_revisions) $sql .= " AND revision_id=(SELECT MAX(revision_id) FROM `" . $db->prefix .$this->tablename."` WHERE ct.exptag_id=".$where." AND ct.content_type='".$this->classname."'";
            $tag_assocs = $db->selectObjectsBySql($sql);
            $records = array();
            foreach ($tag_assocs as $assoc) {
                $records[] = new $this->classname($assoc->id);
            }
            return $records;
        }
    }
    
    public function findBy($column, $value) {
        global $db;
        $where = "`".$column."`=";
        if (!is_numeric($value)) $where .= "'";
        $where .= $value;
        if (!is_numeric($value)) $where .= "'";
        return $this->find('first', $where);
    }
    
    public function update($params=array()) {  
        $this->checkForAttachableItems($params);    
        $this->build($params);
        $this->save(true);  
    }

    public function refresh() {
        if (empty($this->id)) return false;
        $this->__construct($this->id);
    }

   /**
    * Adds table fields as class properties to current "record" class.
    *
    * Loads Table schema data and creates new class properties based
    * upon the fields in given table.
    *
    * Additionally, if a record ID is given, that record is pulled and
    * field values are also populated into class properties.
    *
    * @name build
    *
    * @category db_record
    * @uses [db_type]::getDataDefinition() Builds  a data definition from existing table.
    * @requires $db
    *
    * @access protected
    * @final
    * @PHPUnit Not Defined
    *
    * @global object $db
    * @param mixed $params array or Object for table selection
    *
    * @return none
    * @throws none
    *
    */
    public function build($params=array()) {
        global $db;

        // safeguard against bad data...we can only take objects and arrays here
        if (!is_array($params) && !is_object($params)) $params = array();

        // get the table definition and make sure all the params being passed in belong in this table
        $table = $db->getDataDefinition($this->tablename);
        
        //check for location_data
        if (is_array($params) && (!empty($params['module']) && !empty($params['src']))) {
            $params['location_data'] = serialize(makeLocation($params['module'], $params['src']));
        } elseif(is_object($params) && (!empty($params->module) && !empty($params->src))) {
            $params->location_data = serialize(makeLocation($params->module, $params->src));
        }
    
        // Build Class properties based off table fields
        foreach($table as $col=>$colDef) {              
            // check if the DB column has a corresponding value in the params array
            // if not, we check to see if the column is boolean...if so we set it to false
            // if not, then we check to see if we had a previous value in this particular 
            // record.  if so we reset it to itself so we don't lose the existing value.
            // this is good for when the developer is trying to update just a field or two 
            // in an existing record. 
            if (array_key_exists($col, $params)) {                 
                $value = is_array($params) ? $params[$col] : $params->$col;
                if ($colDef[0] == DB_DEF_INTEGER || $colDef[0] == DB_DEF_ID) {
                    $this->$col = preg_replace("/[^0-9-]/","",$value);
                } elseif ($colDef[0] == DB_DEF_DECIMAL) {
                    $this->$col = preg_replace("/[^0-9.-]/","",$value);
                } else {
                    $this->$col = $value;
                }
            } elseif ($colDef[0] == DB_DEF_BOOLEAN) { 
                $this->$col = empty($this->$col) ? 0 : $this->$col;       
            } elseif ($colDef[0] == DB_DEF_TIMESTAMP) {
                $datename = $col.'date';
                if (is_array($params) && isset($params[$datename])) {
                    $this->$col = yuidatetimecontrol::parseData($col,$params);
                } elseif (is_object($params) && isset($params->$datename)) {
                    $this->$col = yuidatetimecontrol::parseData($col,object_to_array($params));
                } else {
                    $this->$col = !empty($this->$col) ? $this->$col : 0;
                }   
            } else {                
                $this->$col = !empty($this->$col) ? $this->$col : null;
            }
            
            //if (isset($this->col)) {
                $this->$col = stripslashes($this->$col);
            //}
        }
    }

    public function rerank($direction, $where='') {
        global $db;        
        if (!empty($this->rank)) {
            $next_prev = $direction == 'up' ? $this->rank - 1 : $this->rank +1;  
            $where.= empty($this->location_data) ? null : "location_data='".$this->location_data."'";
            $db->switchValues($this->tablename, 'rank', $this->rank, $next_prev, $where);
        }
    }
    
    public function attachItem($item, $subtype='') {
        global $db;
        
        // make sure we have the info we need..otherwise return
        if (empty($item->id) && empty($this->id)) return false;
        global $db;
        // save the attachable items
        $refname = strtolower($item->classname).'s_id';
        $db->delete($item->attachable_table, 'content_type="'.$this->classname.'" AND content_id='.$this->id.' AND '.$refname.'='.$item->id);
        $obj->$refname = $item->id;
        $obj->content_id = $this->id;
        $obj->content_type = $this->classname;
        $obj->subtype = $subtype;
        $obj->rank = 1 + $db->max($item->attachable_table,'rank',null, 'content_type="'.$this->classname.'" AND subtype="'.$subtype.'" AND content_id'.$this->id);
        $db->insertObject($obj, $item->attachable_table);
    }
    
    public function save($validate=false) {
        global $db, $user;
        
        // call the validation callback functions if we need to.
        if ($validate) {            
            $this->beforeValidation();
            $this->validate();
            $this->afterValidation();
        }        
        
        $this->beforeSave();
        
        // Save this object's associated objects to the database.
        // FIXME: we're not going to do this automagically until we get the refreshing figured out.
        //$this->saveAssociatedObjects(); 
        
        //Only grab fields that are valid and save this object
        $saveObj = null;
        $table = $db->getDataDefinition($this->tablename);
        foreach($table as $col=>$colDef) {
            $saveObj->$col = empty($this->$col) ? null : $this->$col;
        }
        
        $identifier = $this->identifier;
       if (!empty($saveObj->$identifier)) { 
            $db->updateObject($saveObj, $this->tablename,null,$identifier,$this->supports_revisions);
            $this->afterUpdate();
        } else {
            $this->$identifier = $db->insertObject($saveObj, $this->tablename);
            $this->afterCreate();
        }

        // run the afterSave callback(s)
        $this->afterSave();
    }

    public function validate() {
        global $db;
        // check for an sef url field.  If it exists make sure it's valid and not a duplicate
        //this needs to check for SEF URLS being turned on also: TODO
              
        if (property_exists($this, 'sef_url') && !(in_array('sef_url',$this->do_not_validate))) {           
            if (empty($this->sef_url)) $this->makeSefUrl();
            $this->validates['is_valid_sef_name']['sef_url'] = array();
            $this->validates['uniqueness_of']['sef_url'] = array();
        }
               
        // safeguard again loc data not being pass via forms...sometimes this happens when you're in a router
        // mapped view and src hasn't been passed in via link to the form 
        if (isset($this->id) && empty($this->location_data)) {
            $loc = $db->selectValue($this->tablename, 'location_data', 'id='.$this->id);
            if (!empty($loc)) $this->location_data = $loc;
        }
        
        // run the validation as defined in the datatypes
        if (!isset($this->validates)) return true;
        $messages = array();
        $post = empty($_POST) ? array() : $_POST;
        foreach ($this->validates as $validation=>$field) {
            foreach($field as $key=>$value) {
                $fieldname = is_numeric($key) ? $value : $key;
                $opts = is_numeric($key) ? array() : $value;
                $ret = expValidator::$validation($fieldname, $this, $opts);
                if(!is_bool($ret)) {
                    $messages[] = $ret;
                    expValidator::setErrorField($fieldname);
                    unset($post[$fieldname]);
                }
            }
        }  
        
        if (count($messages) >= 1) expValidator::failAndReturnToForm($messages, $post);
    }

    public function afterCreate() {
        $this->runCallback('afterCreate');
    }

    public function afterUpdate() {
        $this->runCallback('afterUpdate');
    }

    public function afterSave() {
        global $db;
        $this->runCallback('afterSave');

        // save the attachable items
        if (!empty($this->attachable_items_to_save)) {      
            foreach($this->attachable_item_types as $type) {
                if (!empty($this->attachable_items_to_save[$type])) {
                    $itemtype = new $type();
                    $db->delete($itemtype->attachable_table, 'content_type="'.$this->classname.'" AND content_id='.$this->id);
                    $refname = strtolower($type).'s_id';  //FIXME: find a better way to pluralize these names!!!
                    foreach($this->attachable_items_to_save[$type] as $subtype=>$item) {
                        if (is_array($item)) {
                            foreach($item as $rank=>$value) {
                                if (is_numeric($value)) {
                                    $obj = null;
                                    $obj->$refname = $value;
                                    $obj->subtype = $subtype;
                                    $obj->content_id = $this->id;
                                    $obj->content_type = $this->classname;
                                    if ($type == 'expFile') $obj->rank = $rank + 1;
                                    $db->insertObject($obj, $itemtype->attachable_table);
                                }
                            }
                        } elseif (is_numeric($item)) {
                            $obj = null;
                            $obj->$refname = $item;
                            $obj->content_id = $this->id;
                            $obj->content_type = $this->classname;
                            if ($type == 'expFile') $obj->rank = $subtype + 1;      
                            $db->insertObject($obj, $itemtype->attachable_table);
                        }
                    }
                }
            }
        }
    }

    public function beforeSave() {
        global $user, $db;
        $this->runCallback('beforeSave');
        // populate the magic fields
        if (empty($this->id)) {
            // timestamp the record
            if (property_exists($this, 'created_at')) $this->created_at = time();
            if (property_exists($this, 'edited_at')) $this->edited_at = time();
            // record the user saving the record.
            if (property_exists($this, 'poster')) $this->poster = empty($this->poster) ? $user->id : $this->poster; 
            // fill in the rank field if it exist
            if (property_exists($this, 'rank')) {
                if (empty($this->rank)) {
                    $where = empty($this->location_data) ? null : "location_data='".$this->location_data."'";
                    $groupby = empty($this->location_data) ? null : 'location_data';
                    $this->rank = $db->max($this->tablename, 'rank', $groupby, $where) +1;
                } else {
                    // check if this rank is already there..if so increment everything below it.
                    $obj = $db->selectObject($this->tablename, 'rank='.$this->rank);
                    if (!empty($obj)) {
                        $db->increment($this->tablename,'rank',1,'rank>='.$this->rank);
                        
                    }
                }
            }
            
            $this->beforeCreate();
        } else {
            // put the created_at time back the way it was so we don't set it 0
            if (property_exists($this, 'created_at') && $this->created_at == 0) {
                $this->created_at = $db->selectValue($this->tablename, 'created_at', 'id='.$this->id);
            }
            
            // put the original posters id back the way it was so we don't set it 0
            if (property_exists($this, 'poster') && $this->poster == 0) {
                $this->poster = $db->selectValue($this->tablename, 'poster', 'id='.$this->id);
            }
            
            //put the rank back to what it was so we don't set it 0
            if (property_exists($this, 'rank') && $this->rank == 0) {
                $this->rank = $db->selectValue($this->tablename, 'rank', 'id='.$this->id);
            }
            
            if (property_exists($this, 'edited_at')) $this->edited_at = time();
            if (property_exists($this, 'editor')) $this->editor = $user->id;
            $this->beforeUpdate();
        }
    }

    public function beforeCreate() {
        $this->runCallback('beforeCreate');
    }

    public function beforeUpdate() {
        $this->runCallback('beforeUpdate');
    }

    public function beforeValidation() {
        $this->runCallback('beforeValidation');
        if (empty($this->id)) {
            $this->beforeValidationOnCreate();
        } else {
            $this->beforeValidationOnUpdate();
        }
    }

    public function beforeValidationOnCreate() {
        $this->runCallback('beforeValidationOnCreate');
    }

    public function beforeValidationOnUpdate() {
        $this->runCallback('beforeValidationOnUpdate');
    }

    public function afterValidation() {
        $this->runCallback('afterValidation');
        if (empty($this->id)) {
            $this->afterValidationOnCreate();
        } else {
            $this->afterValidationOnUpdate();
        }
    }

    public function afterValidationOnCreate() {
        $this->runCallback('afterValidationOnCreate');
    }

    public function afterValidationOnUpdate() {
        $this->runCallback('afterValidationOnUpdate');
    }

    private function runCallback($type) {
        if (empty($type)) return false;

        // check for and run any callbacks listed in the $type array.
        if ($this->classinfo->hasProperty($type)) {
            $callbacks = $this->classinfo->getProperty($type);
            foreach($callbacks->getValue(new $this->classname(null,false,false)) as $func) {
                $this->$func();
            }
        }
    }

    public function delete($where = '') {
        global $db;
        if (empty($this->id)) return false;
        $db->delete($this->tablename,'id='.$this->id);
        if (!empty($where)) $where .= ' AND ';
        if (property_exists($this, 'rank')) $db->decrement($this->tablename,'rank',1, $where . 'rank>='.$this->rank);
        
        // delete attached items
        foreach($this->attachable_item_types as $content_table=>$type) {
            $db->delete($content_table, 'content_type="'.$this->classname.'" AND content_id='.$this->id);
        }
    }

    public function makeSefUrl() {
        global $db, $router;        
        if (isset($this->title)) $this->sef_url = $router->encode($this->title);
        $dupe = $db->selectValue($this->tablename, 'sef_url', 'sef_url="'.$this->sef_url.'"');
        if (!empty($dupe)) $this->sef_url .= '-'.time();
        $this->runCallback('makeSefUrl');
    }

    public function getAssociatedObjects($obj=null) {
        global $db;
        $records = array();
        
        foreach ($this->has_one as $assoc_object) {
            $ret = $db->selectObjects($this->tablename, $assoc_object.'_id='.$obj->id);
            if (!empty($ret)) $obj->$assoc_object = $ret;
        }

        foreach($this->has_many as $assoc_object) {
            $ret = $db->selectObjects($assoc_object, $this->tablename.'_id='.$obj->id);
            if (!empty($ret)) $obj->$assoc_object = $ret;
        }

        foreach($this->has_and_belongs_to_many as $assoc_object) {
            if (strcmp($this->tablename, $assoc_object) > 0) {
                $tablename = $assoc_object.'_'.$this->tablename;
            } else {
                $tablename = $this->tablename.'_'.$assoc_object;
            } 
                        
            //$ret = $db->selectObjects($tablename, $this->tablename.'_id='.$obj->id);
            $instances = $db->selectObjects($tablename, $this->tablename.'_id='.$obj->id);
            $ret = array();
            foreach ($instances as $instance) {
                $fieldname = $assoc_object.'_id';
                $ret[] = $db->selectObject($assoc_object, 'id='.$instance->$fieldname);
            }
            if (!empty($ret)) $obj->$assoc_object = $ret;
        }
        
        return $obj;
    }
    
    // this function finds models at that have this attachable item attached to it
    public function findWhereAttachedTo($content_type) {
        global $db;
        $objarray = array();
        if (!empty($this->id) && !empty($this->attachable_table)) {
            $assocs = $db->selectObjects($this->attachable_table, $this->classname.'s_id='.$this->id.' AND content_type="'.$content_type.'"');
            foreach($assocs as $assoc) {
                $objarray[] = new $assoc->content_type($assoc->content_id);
            }
        }
        
        return $objarray;
    }

    private function checkForAttachableItems($params) {
        if (empty($params)) return false;
        foreach ($this->attachable_item_types as $type) {
            if (array_key_exists($type, $params)) {
                $this->attachable_items_to_save[$type] = is_array($params) ? $params[$type] : $params->$type;
            }
        }
    }

    protected function getAttachableItems() {
        global $db;
    
        foreach($this->attachable_item_types as $content_table=>$type) {
            if ($this->classname == $type) break;
            
            $tablename = str_ireplace('content_', '', $content_table);
            if (!isset($this->id)) {
                $this->$type = array();
            } else {
                $order = ($type == 'expFile') ? ' ORDER BY rank ASC' : null;
                
                $sql  = 'SELECT ef.*, cef.subtype AS subtype FROM ';
                $sql .= DB_TABLE_PREFIX.'_'.$tablename.' ef JOIN '.DB_TABLE_PREFIX.'_'.$content_table.' cef ';
                $sql .= "ON ef.id = cef.".$tablename."_id";
                $sql .= " WHERE content_id=".$this->id;
                $sql .= " AND content_type='".$this->classname."'";
                if ($type == 'expComment') {
                    $sql .= " AND approved='1'";
                }
                $sql .= $order;
                
                $items = $db->selectArraysBySql($sql);
                
                $attacheditems = array();
                foreach($items as $item) {
                    //FIXME: find a better way to unpluralize the name!
                    $idname = strtolower($type).'_id';
                    if (empty($item['subtype'])) {
                        $attacheditems[] = new $type($item, false, false);
                    } else {
                        if (!isset($attacheditems[$item['subtype']])) $attacheditems[$item['subtype']] = array();
                        $attacheditems[$item['subtype']][] = new $type($item, false, false);
                    }
                }
                
                $this->$type = $attacheditems;
            }
        }
    }

    private function getAssociatedObjectsForThisModel($except=array()) {
        global $db;
        foreach ($this->has_extended_fields as $assoc_object) {
            // figure out the name of the model based off the models tablename
            $obj = new $assoc_object(null, false, false);
            $this->$assoc_object = $obj->find('first', $this->tablename.'_id = ' . $this->id);
        }
        //this requires a field in the table only with the ID of the associated object we're looking for in its table
        foreach ($this->has_one as $assoc_object) {
            // figure out the name of the model based off the models tablename
            $obj = new $assoc_object(null, false, false);
            $id_name = $obj->tablename.'_id';
          
            // check to see if we have an association yet.  if not we'll initialize an empty model
            $id = empty($this->$id_name) ? array() : $this->$id_name;
            
            $this->$assoc_object = new $assoc_object($id, in_array($assoc_object, $this->get_assoc_for), in_array($assoc_object, $this->get_attachable_for));
        }
        //perhaps add a 'in' option to the find so we can pass an array of ids and make ONE db call instead of looping
        foreach($this->has_many as $assoc_object) {                     
            $assoc_obj = new $assoc_object();
            $ret = $db->selectArrays($assoc_obj->tablename, $this->tablename.'_id='.$this->id, $assoc_obj->default_sort_field != '' ? $assoc_obj->default_sort_field . " " . $assoc_obj->default_sort_direction : null);
            $records = array();
            foreach ($ret as $record) {
                $records[] = new $assoc_object($record, in_array($assoc_object, $this->get_assoc_for), in_array($assoc_object, $this->get_attachable_for));
            }
            $this->$assoc_object = $records;
        }

        foreach($this->has_many_self as $assoc_object) {                     
            $assoc_obj = new $assoc_object();
            $ret = $db->selectArrays($assoc_obj->tablename, $assoc_obj->has_many_self_id. '=' .$this->id);
            $records = array();
            foreach ($ret as $record) {
                $records[] = new $assoc_object($record, in_array($assoc_object, $this->get_assoc_for), in_array($assoc_object, $this->get_attachable_for));
            }
            $this->$assoc_object = $records;
        }
        
        foreach($this->has_and_belongs_to_many as $assoc_object) {
            if (!in_array($assoc_object, $except)) {
                $assocObj = new $assoc_object(null, false, false);
                $tablename = $this->makeManyToManyTablename($assocObj->tablename);
                
                $ret = $db->selectObjects($assocObj->tablename, 'id IN (SELECT '.$assocObj->tablename.'_id from '.DB_TABLE_PREFIX.'_'.$tablename.' WHERE '.$this->tablename.'_id='.$this->id.')');
                $records = array();
                foreach ($ret as $record) {
                    $record_array = $this->object2Array($record);
                    // put in the current model as an exception, otherwise the auto assoc's keep initializing instances of each other in an
                    // infinant loop
                    $record_array['except'] = array($this->classinfo->name);
                    $records[] = new $assoc_object($record_array, in_array($assoc_object, $this->get_assoc_for), in_array($assoc_object, $this->get_attachable_for));
                }
                $this->$assoc_object = $records;
            }
        }
        
        foreach($this->has_and_belongs_to_self as $assoc_object) {
            if (!in_array($assoc_object, $except)) {
                $assocObj = new $assoc_object(null, false, false);
                $tablename = $this->makeManyToManyTablename($assocObj->classname);
                
                $ret = $db->selectObjects($assocObj->tablename, 'id IN (SELECT '.$assocObj->classname.'_id from '.DB_TABLE_PREFIX.'_'.$tablename.' WHERE '.$this->tablename.'_id='.$this->id.')');
                $records = array();
                foreach ($ret as $record) {
                    $record_array = $this->object2Array($record);
                    // put in the current model as an exception, otherwise the auto assoc's keep initializing instances of each other in an
                    // infinant loop
                    $record_array['except'] = array($this->classinfo->name);
                    $records[] = new $assoc_object($record_array, in_array($assoc_object, $this->get_assoc_for), in_array($assoc_object, $this->get_attachable_for));
                }
                $this->$assoc_object = $records;
            }
        }
    }

    public function associateWith($datatype, $id) {
        global $db;
        
        $assocObj = new $datatype();

        if (in_array($datatype, $this->has_and_belongs_to_many)) {
            $tablename = $this->makeManyToManyTablename($assocObj->tablename);
            $thisid = $this->tablename.'_id';
            $otherid = $assocObj->tablename.'_id';
            $obj->$thisid = $this->id;
            $obj->$otherid = $id;
            $db->insertObject($obj, $tablename);
        }
    }
    
    public function saveAssociatedObjects() {
        global $db;

        foreach ($this->has_one as $assoc_object) {
            $obj = $this->$assoc_object;
            $obj->save();
            
            $assoc_id_name = $assoc_object.'_id';
            $this->$assoc_id_name = $obj->id;
        }
    }
    //why the copmare to flip order?
    private function makeManyToManyTablename($assoc_table) {
        if ( strcmp($this->tablename, $assoc_table) > 0) {
            $tablename = $assoc_table.'_'.$this->tablename;
        } else {
            $tablename = $this->tablename.'_'.$assoc_table;
        }
        return $tablename;
    }
    
    private function object2Array($object=null) {
        $ret_array = array();
        if(empty($object)) return $ret_array;

        foreach($object as $key=>$value) {
            $ret_array[$key] = $value;
        }
        
        return $ret_array;
    }

};

?>