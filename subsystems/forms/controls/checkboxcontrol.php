<?php

##################################################
#
# Copyright (c) 2004-2006 OIC Group, Inc.
# Written and Designed by Phillip Ball
#
# This file is part of Exponent
#
# Exponent is free software; you can redistribute
# it and/or modify it under the terms of the GNU
# General Public License as published by the Free
# Software Foundation; either version 2 of the
# License, or (at your option) any later version.
#
# GPL: http://www.gnu.org/licenses/gpl.txt
#
##################################################

if (!defined('EXPONENT')) exit('');

/**
 * Check Box Control
 *
 * An HTML checkbox
 *
 * @author Phillip Ball
 * @copyright 2004-2051 OIC Group, Inc.
 * @version 0.96.7
 *
 * @package Subsystems
 * @subpackage Forms
 */

/**
 * Manually include the class file for formcontrol, for PHP4
 * (This does not adversely affect PHP5)
 */
require_once(BASE."subsystems/forms/controls/formcontrol.php");

/**
 * Check Box Control class
 *
 * An HTML checkbox
 *
 * @package Subsystems
 * @subpackage Forms
 */
class checkboxcontrol extends formcontrol {
    var $flip = false;
    var $jsHooks = array();
    
    function name() { return "Checkbox"; }
    function isSimpleControl() { return true; }
    function useGeneric() { return false; }
    
    function getFieldDefinition() { 
        return array(
            DB_FIELD_TYPE=>DB_DEF_BOOLEAN);
    }

    function checkboxcontrol($default = 1,$flip = false,$required = false) {
        $this->default = $default;
        $this->flip = $flip;
        $this->jsHooks = array();
        $this->required = $required;
        $this->nowrap = false;
    }
    
     function toHTML($label,$name) {
            if (!empty($this->id)) {
		        $divID  = ' id="'.$this->id.'Control"';
		        $for = ' for="'.$this->id.'"';
		    } else {
		        $divID  = '';
		        $for = '';
		    }
            $html = "<div".$divID." class=\"control checkbox";
            $html .= (!empty($this->required)) ? ' required">' : '">';
            $html .= "<table border=0 cellpadding=0 cellspacing=0><tr>";
        if(!empty($this->flip)){
            $html .= "<td class=\"input\">";
            $html .= "<label".$for." class=\"label ".$this->nowrap."\">".$label."</label>";
            $html .= "</td><td>";
            $html .= isset($this->newschool) ? $this->controlToHTML_newschool($name, $label) :$this->controlToHTML($name);
            $html .= "</td>";           
        }else{
            $html .= "<td class=\"input\">";
            $html .= isset($this->newschool) ? $this->controlToHTML_newschool($name, $label) :$this->controlToHTML($name);
            $html .= "</td><td>";
            $html .= "<label".$for." class=\"label ".$this->nowrap."\">".$label."</label>";
            $html .= "</td>";           
        }
            $html .= "</tr></table>";
            $html .= "</div>";                      
        return $html;
     }
    
/*
    function toHTML($label,$name) {
        if(empty($this->flipped)){
            $html = '<label>';
            $html .= $this->controlToHTML($name);
            $html .= "<span class=\"checkboxlabel\">".$label."</span>";
            $html .= "</label>";                
        }else{
            $html = '<label>';
            $html .= "<span class=\"checkboxlabel\">".$label."</span>";
            $html .= $this->controlToHTML($name);
            $html .= "</label>";                
        }
        return $html;
    }
*/


    function controlToHTML($name) {
        $inputID  = (!empty($this->id)) ? ' id="'.$this->id.'"' : "";
		$val = 1;
		$html = "";

	    $html .= '<input'.$inputID.' class="checkbox" type="checkbox" name="' . $name . '" value="'.$val.'"';
	    if ($this->default) $html .= ' checked="checked"';
	    if ($this->tabindex >= 0) $html .= ' tabindex="' . $this->tabindex . '"';
	    if ($this->accesskey != "") $html .= ' accesskey="' . $this->accesskey . '"';
	    if ($this->disabled) $html .= ' disabled';
	    foreach ($this->jsHooks as $type=>$val) {
	        $html .= ' '.$type.'="'.$val.'"';
	    }
	    if (@$this->required) {
	        $html .= 'required="'.rawurlencode($this->default).'" caption="'.rawurlencode($this->caption).'" ';
	    }
	    $html .= ' />';
	    return $html;
	}
    
    //FIXME:  this is just here until we completely deprecate the old school checkbox
    //control calls in the old school forms
    function controlToHTML_newschool($name, $label) {
        $inputID  = (!empty($this->id)) ? ' id="'.$this->id.'"' : "";
        $this->name = empty($this->name) ? $name : $this->name;

		$html = "";
        // hidden value to force a false value in to the post array
        // if unchecked, the index won't even get in to the post array
        if ($this->postfalse) {
    	    $html .= '<input type="hidden" name="' . $name . '" value="0" />';	
        }

        $html .= '<input'.$inputID.' type="checkbox" name="' . $this->name . '" value="'.$this->value.'"';
        if ($this->size) $html .= ' size="' . $this->size . '"';
        if ($this->checked) $html .= ' checked="checked"';
        $html .= !empty($this->class)?' class="'.$this->class.' checkbox"':' class="checkbox"';
        if ($this->tabindex >= 0) $html .= ' tabindex="' . $this->tabindex . '"';
        if ($this->accesskey != "") $html .= ' accesskey="' . $this->accesskey . '"';
        if ($this->filter != "") {
            $html .= " onkeypress=\"return ".$this->filter."_filter.on_key_press(this, event);\" ";
            $html .= "onblur=\"".$this->filter."_filter.onblur(this);\" ";
            $html .= "onfocus=\"".$this->filter."_filter.onfocus(this);\" ";
            $html .= "onpaste=\"return ".$this->filter."_filter.onpaste(this, event);\" ";
        }
        if ($this->disabled) $html .= ' disabled';
        foreach ($this->jsHooks as $type=>$val) {
            $html .= ' '.$type.'="'.$val.'"';
        }

        if (!empty($this->readonly)) $html .= ' readonly="readonly"';

        $caption = isset($this->caption) ? $this->caption : str_replace(array(":","*"), "", ucwords($label));
        if (!empty($this->required)) $html .= ' required="'.rawurlencode($this->default).'" caption="'.$caption.'" ';
        if (!empty($this->onclick)) $html .= ' onclick="'.$this->onclick.'" ';
        if (!empty($this->onchange)) $html .= ' onchange="'.$this->onchange.'" ';

        $html .= ' />';
        return $html;
    }
    function parseData($name, $values, $for_db = false) {
        return isset($values[$name])?1:0;
    }
    
    function templateFormat($db_data, $ctl) {
        return ($db_data==1)?"Yes":"No";
    }
    
    function form($object) {
        $i18n = exponent_lang_loadFile('subsystems/forms/controls/checkboxcontrol.php');
    
        if (!defined("SYS_FORMS")) require_once(BASE."subsystems/forms.php");
        exponent_forms_initialize();
    
        $form = new form();
        if (!isset($object->identifier)) {
            $object->identifier = "";
            $object->caption = "";
            $object->default = false;
            $object->flip = false;
            $object->required = false;
        } 
        
        $form->register("identifier",$i18n['identifier'],new textcontrol($object->identifier));
        $form->register("caption",$i18n['caption'], new textcontrol($object->caption));
        $form->register("default",$i18n['default'], new checkboxcontrol($object->default,false));
        $form->register("flip",$i18n['caption_right'], new checkboxcontrol($object->flip,false));
        $form->register(null, null, new htmlcontrol('<br />'));
        $form->register("required", $i18n['required'], new checkboxcontrol($object->required,true));
        $form->register(null, null, new htmlcontrol('<br />')); 
        $form->register("submit","",new buttongroupcontrol($i18n['save'],'',$i18n['cancel']));
        
        return $form;
    }
    
    function update($values, $object) {
        if ($object == null) $object = new checkboxcontrol();
        if ($values['identifier'] == "") {
            $i18n = exponent_lang_loadFile('subsystems/forms/controls/checkboxcontrol.php');
        
            $post = $_POST;
            $post['_formError'] = $i18n['id_required'];
            exponent_sessions_set("last_POST",$post);
            return null;
        }
        $object->identifier = $values['identifier'];
        $object->caption = $values['caption'];
        $object->default = isset($values['default']);
        $object->flip = isset($values['flip']);
        $object->required = isset($values['required']);
        return $object;
    }
    
}

?>
