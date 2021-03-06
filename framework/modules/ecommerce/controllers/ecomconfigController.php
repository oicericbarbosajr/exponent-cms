<?php

##################################################
#
# Copyright (c) 2004-2006 OIC Group, Inc.
# Created by Adam Kessler @ 05/28/2008
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

class ecomconfigController extends expController {
	//public $basemodel_name = 'expRecord';
	//public $useractions = array('show'=>'Configuration Panel');
	public $useractions = array();
    public $add_permissions = array('show'=>'View Admin Options');
	
	function name() { return $this->displayname(); } //for backwards compat with old modules
    function displayname() { return "Ecommerce Configuration Manager"; }
    function description() { return "Use this module to configure your Ecommerce store"; }
    function author() { return "Adam Kessler @ OIC Group, Inc"; }
    function hasSources() { return false; }
    function hasViews() { return true; }
	function hasContent() { return true; }
	function supportsWorkflow() { return false; }

    function show() {
        expHistory::set('managable', $this->params);
    }
    
    /*****************************************************************/
    /***************  PRODUCT OPTIONS *******************************/
    /*****************************************************************/
    function edit_optiongroup_master() {
        expHistory::set('editable', $this->params);
        
        $id = isset($this->params['id']) ? $this->params['id'] : null;
        $record = new optiongroup_master($id);       
        assign_to_template(array('record'=>$record));
    }
    
    function update_optiongroup_master() {
        global $db;
        $id = empty($this->params['id']) ? null : $this->params['id'];
        $og = new optiongroup_master($id);
        $oldtitle = $og->title;
        $og->update($this->params);
        
        // if the title of the master changed we should update the option groups that are already using it.
        if ($oldtitle != $og->title) {
            $db->sql('UPDATE '.DB_TABLE_PREFIX.'_optiongroup SET title="'.$og->title.'" WHERE title="'.$oldtitle.'"');
        }
        
        expHistory::back();
    }
    
    function delete_optiongroup_master() {
        global $db;
        
        $mastergroup = new optiongroup_master($this->params);
        
        // delete all the options for this optiongroup master
        foreach ($mastergroup->option_master as $masteroption) {
            $db->delete('option', 'option_master_id='.$masteroption->id);
            $masteroption->delete();
        }
        
        // delete the mastergroup
        $db->delete('optiongroup', 'optiongroup_master_id='.$mastergroup->id);
        $mastergroup->delete();
        
        expHistory::back();
    }
    
    function delete_option_master() {
        global $db;
        $masteroption = new option_master($this->params['id']);
        
        // delete any implementations of this option master
        $db->delete('option', 'option_master_id='.$masteroption->id);
        $masteroption->delete('optiongroup_master_id=' . $masteroption->optiongroup_master_id);
        //eDebug($masteroption);
        expHistory::back();
    }
    
    function edit_option_master() {
        expHistory::set('editable', $this->params);
        
        $params = isset($this->params['id']) ? $this->params['id'] : $this->params;
        $record = new option_master($params);      
        assign_to_template(array('record'=>$record));
    }
    
    function update_option_master() {        
        global $db;
        $id = empty($this->params['id']) ? null : $this->params['id'];
        $opt = new option_master($id);
        $oldtitle = $opt->title;
        
        $opt->update($this->params);
        
        // if the title of the master changed we should update the option groups that are already using it.
        if ($oldtitle != $opt->title) {
            
        }$db->sql('UPDATE '.DB_TABLE_PREFIX.'_option SET title="'.$opt->title.'" WHERE option_master_id='.$opt->id);
        
        expHistory::back();
    }
    
    public function options() {
        expHistory::set('viewable', $this->params);
        $optiongroup = new optiongroup_master();
        $optiongroups = $optiongroup->find('all');
        assign_to_template(array('optiongroups'=>$optiongroups));
    }
    
    function rerank_optionmaster() {
        $om = new option_master($this->params['id']);
        $om->rerank($this->params['push'], 'optiongroup_master_id=' . $this->params['master_id']);
        expHistory::back();
    }
    
    /*****************************************************************/
    /***************  DISCOUNTS        *******************************/
    /*****************************************************************/
    public function manage_discounts() {
        expHistory::set('managable', $this->params);
        $discountObj = new discounts();
        $discounts = $discountObj->find('all');
        assign_to_template(array(/*'apply_rules'=>$discountObj->apply_rules, 'discount_types'=>$discountObj->discount_types,*/'discounts'=>$discounts));
    }
    
      public function edit_discount() {
        $id = empty($this->params['id']) ? null : $this->params['id'];
        $discount = new discounts($id);
        
        //grab all user groups
        $group = new group();
        
        //create two 'default' groups:
        $groups = array( 
                -1 => 'ALL LOGGED IN USERS',
                -2 => 'ALL NON-LOGGED IN USERS'
                );
        //loop our groups and append them to the array
       // foreach ($group->find() as $g){
       //this is a workaround for older code. Use the previous line if possible:
       include_once(BASE.'subsystems//users.php');
       $allGroups = exponent_users_getAllGroups();
       if (count($allGroups))
       {
           foreach ($allGroups as $g)
           {
                $groups[$g->id] = $g->name;
           };
       }
       //find our selected groups for this discount already
       // eDebug($discount);                        
       $selected_groups = array();
       if (!empty($discount->group_ids))
       {
            $selected_groups = expUnserialize($discount->group_ids);
       }
       
       if ($discount->minimum_order_amount == "") $discount->minimum_order_amount = 0;
       if ($discount->discount_amount == "") $discount->discount_amount = 0;
       if ($discount->discount_percent == "") $discount->discount_percent = 0;
       
       assign_to_template(array('discount'=>$discount, 'groups'=>$groups, 'selected_groups'=>$selected_groups));
    }
    
    public function update_discount() {
        $id = empty($this->params['id']) ? null : $this->params['id'];
        $discount = new discounts($id);
        $discount->update($this->params);
        expHistory::back();
    }
    
    public function activate_discount(){    
        if (isset($this->params['id'])) {
            $discount = new discounts($this->params['id']);
            $discount->update($this->params);
            //if ($discount->discountulator->hasConfig() && empty($discount->config)) {
                //flash('messages', $discount->discountulator->name().' requires configuration. Please do so now.');
                //redirect_to(array('controller'=>'billing', 'action'=>'configure', 'id'=>$discount->id));
            //}
        }
        
        expHistory::back();
    }
    
    /*****************************************************************/
    /***************  PROMO CODE       *******************************/
    /*****************************************************************/
	public function manage_promocodes() {
		expHistory::set('managable', $this->params);
        $pc = new promocodes();
        $do = new discounts();
        $promo_codes = $pc->find('all');
        $discounts = $do->find('all');
		assign_to_template(array('promo_codes'=>$promo_codes, 'discounts'=>$discounts));
	}

	public function update_promocode() {
	    global $db;
	    //$id = empty($this->params['id']) ? null : $this->params['id'];
	    $code = new promocodes();
	    $code->update($this->params);
	    expHistory::back();
	}
	
    /*****************************************************************/
    /***************  GROUP DISCOUNTS  *******************************/
    /*****************************************************************/
	public function manage_groupdiscounts() {
		global $db;
		expHistory::set('managable', $this->params);
		$groups = exponent_users_getAllGroups();
		$discounts = $db->selectObjects('discounts');
		$group_discounts = $db->selectObjects('groupdiscounts', null, 'rank');
		assign_to_template(array('groups'=>$groups,'discounts'=>$discounts,'group_discounts'=>$group_discounts));
	}

	public function update_groupdiscounts() {
	    global $db;
	    
	    if (empty($this->params['id'])) {
	        // look for existing discounts for the same group
	        $existing_id = $db->selectValue('groupdiscounts', 'id', 'group_id='.$this->params['group_id']);
	        if (!empty($existing_id)) flashAndFlow('error', 'There is already a discount for that group.');	        
	    }

        $gd = new groupdiscounts();
	    $gd->update($this->params);
	    expHistory::back();
	}
	
	function rerank_groupdiscount() {
        $gd = new groupdiscounts($this->params['id']);
        $gd->rerank($this->params['push']);
        expHistory::back();
    }
    
    /*****************************************************************/
    /***************  GENERAL STORE CONFIG  *******************************/
    /*****************************************************************/
    function configure() {
        expHistory::set('editable', $this->params);
        // little bit of trickery so that that categories can have their own configs
        
        $this->loc->src = "@globalstoresettings";
        $config = new expConfig($this->loc);
        $this->config = $config->config;
        $pullable_modules = listInstalledControllers($this->classname, $this->loc);
        $views = get_config_templates($this, $this->loc);
        assign_to_template(array('config'=>$this->config, 'pullable_modules'=>$pullable_modules, 'views'=>$views));
    }    
}

?>
