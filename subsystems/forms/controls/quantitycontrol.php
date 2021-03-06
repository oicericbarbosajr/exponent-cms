<?php

##################################################
#
# Copyright (c) 2004-2006 OIC Group, Inc.
# Written and Designed by James Hunt
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
 * Quantity Control
 *
 * @author Adam Kessler
 * @copyright 2004-2008 OIC Group, Inc.
 * @version 0.97
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
 * Quantity Control
 *
 * @package Subsystems
 * @subpackage Forms
 */
class quantitycontrol extends formcontrol {
    public $min=0;
    public $max=99999;

    function name() { return "Quantity Widget"; }
    function isSimpleControl() { return false; }
    function getFieldDefinition() {
        return array(
            DB_FIELD_TYPE=>DB_DEF_STRING,
            DB_FIELD_LEN=>10000);
    }
    
    function __construct($default="",$min=0,$max=99999, $size=null) {
        $this->type = "quantity";
        $this->default = $default;
        $this->min = $min;
        $this->max = $max;
        $this->required = false;
        $this->size = isset($size) ? $size : strlen(strval($max));
    }
    function toHTML($label,$name) {
        $this->id  = (empty($this->id)) ? $name : $this->id;
        $html = "<div id=\"".$this->id."Control\" class=\"control";
        $html .= (!empty($this->required)) ? ' required">' : '">';
        //$html .= "<span class=\"label\">".$label."</span>";
        $html .= $this->controlToHTML($name, $label);
        $html .= "<div id=\"udq-".$this->id."\" class=\"updatingQuantity hide\"></div></div>";          
        return $html;
    }

    function controlToHTML($name, $label) {
        $this->name = empty($this->name) ? $name : $this->name;
                $this->id = empty($this->id) ? $name : $this->id;

        $html  = '<table height=100% class="quantity-tbl" cellspacing="0" cellpadding="0"><tr><td style="padding:3px;">';
                $html .= '<input type="text" id="' . $this->id . '" name="' . $this->name . '" value="'.$this->default.'"';
                if ($this->size) $html .= ' size="' . $this->size . '"';
                $html .= ' class="'.$this->type. " " . $this->class . '"';
                if ($this->tabindex >= 0) $html .= ' tabindex="' . $this->tabindex . '"';
                if ($this->accesskey != "") $html .= ' accesskey="' . $this->accesskey . '"';
                if ($this->filter != "") {
                        $html .= " onkeypress=\"return ".$this->filter."_filter.on_key_press(this, event);\" ";
                        $html .= "onblur=\"".$this->filter."_filter.onblur(this);\" ";
                        $html .= "onfocus=\"".$this->filter."_filter.onfocus(this);\" ";
                        $html .= "onpaste=\"return ".$this->filter."_filter.onpaste(this, event);\" ";
                }
                if ($this->disabled) $html .= ' disabled';
                if (!empty($this->readonly)) $html .= ' readonly="readonly"';

                $caption = isset($this->caption) ? $this->caption : str_replace(array(":","*"), "", ucwords($label));
                if (!empty($this->required)) $html .= ' required="'.rawurlencode($this->default).'" caption="'.$caption.'" ';
                if (!empty($this->onclick)) $html .= ' onclick="'.$this->onclick.'" ';
                if (!empty($this->onchange)) $html .= ' onchange="'.$this->onchange.'" ';

                $html .= ' /></td>';
        $html .= '<td width="14" style="padding:0;">
            <table style="float:left;margin:0;"><tr><td style="padding:0;">
            <a id="up-'.$this->id.' " class="uptick" href="javascript:void(0);" style="float:left;">
            <img style="margin:0;padding:0;font-size:0;line-height:0;float:left;" src="'.ICON_RELATIVE.'quantity-up.png"'.XHTML_CLOSING.'>
            </a></td></tr><tr><td style="padding:0;">
            <a id="down-'.$this->id.' " class="downtick" href="javascript:void(0);" style="float:left;">
            <img style="margin:0;padding:0;font-size:0;line-height:0;float:left;" src="'.ICON_RELATIVE.'quantity-down.png"'.XHTML_CLOSING.'>
            </a></td></tr></table>
            </td></tr></table>';

        // if this control is using an ajax action then lets set up a variable for the function call
        $ajaxaction = isset($this->ajaxaction) ?  $this->ajaxaction."(id, value);" : ''; 

        // setup the JS to be used by this control.
        $script = "
            (function() {
                EXPONENT.onQuantityAdjusted = new YAHOO.util.CustomEvent('Quantity Adjusted');
                var upItems = YAHOO.util.Dom.getElementsByClassName('uptick');
                var downItems = YAHOO.util.Dom.getElementsByClassName('downtick');
                var values = YAHOO.util.Dom.getElementsByClassName('".$this->type."');
                YAHOO.util.Event.on(upItems, 'click', incrementQuantity);
                YAHOO.util.Event.on(downItems, 'click', decrementQuantity);
                YAHOO.util.Event.on(values, 'change', checkAjaxAction);
        
                function incrementQuantity(e) { 
                    YAHOO.util.Event.stopEvent(e);
                    var el = YAHOO.util.Dom.getAncestorByTagName(YAHOO.util.Event.getTarget(e), 'a'); 
                    var qtyID = el.id.replace('up-', '').replace(' ', '');
                    changeQuantity(qtyID, 1);
                };
    
                function decrementQuantity(e) { 
                    YAHOO.util.Event.stopEvent(e);
                    var el = YAHOO.util.Dom.getAncestorByTagName(YAHOO.util.Event.getTarget(e), 'a'); 
                    var qtyID = el.id.replace('down-', '').replace(' ', '');
                    changeQuantity(qtyID, -1);
                }
    
                function changeQuantity(qtyID, value) {
                    var qtyBox = YAHOO.util.Dom.get(qtyID);
                    var newval = parseInt(qtyBox.value) + parseInt(value);
		            if (newval < 1) return 1;
                    if (newval >= ".$this->min." && newval <= ".$this->max.") {
                        qtyBox.value = newval;
                        callAjaxAction(qtyBox.id, newval);
                    }
                }

                function checkAjaxAction(e) {
                    var qtyBox = YAHOO.util.Event.getTarget(e);
                    if (qtyBox.value < ".$this->min.") {
                        qtyBox.value = ".$this->min.";
                    } else if (qtyBox.value > ".$this->max.") {
                        qtyBox.value = ".$this->max.";
                    }
                    
                    callAjaxAction(qtyBox.id, qtyBox.value);
                }

                function callAjaxAction(id, value) {
                    ".$ajaxaction."
                }
            })();
        ";

        $extfile = isset($this->loadjsfile) ? $this->loadjsfile : null;
        exponent_javascript_toFoot('qty', 'json,connection', null, $script, $extfile);
        return $html;
    }
    
    function form($object) {
        if (!defined("SYS_FORMS")) require_once(BASE."subsystems/forms.php");
        exponent_forms_initialize();
    
        $form = new form();
        
        if (!isset($object->identifier)) {
            $object->identifier = "";
            $object->caption = "";
            $object->default = "";
            $object->rows = 20;
            $object->cols = 60;
            $object->maxchars = 0;
        } 
        
        $i18n = exponent_lang_loadFile('subsystems/forms/controls/texteditorcontrol.php');
        
        $form->register("identifier",$i18n['identifier'],new textcontrol($object->identifier));
        $form->register("caption",$i18n['caption'], new textcontrol($object->caption));
        $form->register("default",$i18n['default'],  new texteditorcontrol($object->default));
        $form->register("rows",$i18n['rows'], new textcontrol($object->rows,4,false,3,"integer"));
        $form->register("cols",$i18n['cols'], new textcontrol($object->cols,4, false,3,"integer"));
        $form->register("submit","",new buttongroupcontrol($i18n['save'],'',$i18n['cancel']));
        return $form;
    }
    
    function update($values, $object) {
        if ($object == null) $object = new texteditorcontrol();
        if ($values['identifier'] == "") {
            $i18n = exponent_lang_loadFile('subsystems/forms/controls/texteditorcontrol.php');
            $post = $_POST;
            $post['_formError'] = $i18n['id_req'];
            exponent_sessions_set("last_POST",$post);
            return null;
        }
        $object->identifier = $values['identifier'];
        $object->caption = $values['caption'];
        $object->default = $values['default'];
        $object->rows = intval($values['rows']);
        $object->cols = intval($values['cols']);
        $object->maxchars = intval($values['maxchars']);
        $object->required = isset($values['required']);
        
        return $object;
    
    }
    
    function parseData($original_name,$formvalues,$for_db = false) {
        return str_replace(array("\r\n","\n","\r"),'<br />', htmlspecialchars($formvalues[$original_name])); 
    }
    
}

?>
