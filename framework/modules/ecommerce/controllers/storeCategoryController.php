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

class storeCategoryController extends expNestedNodeController {
	
	function name() { return $this->displayname(); } //for backwards compat with old modules
	function displayname() { return "Store Category Manager"; }
	function description() { return "This module is for manageing categories in your store."; }
	function author() { return "OIC Group, Inc"; }
	function hasSources() { return true; }
	function hasViews() { return true; }

	protected $add_permissions = array('fix_categories'=>'to do what you are trying to so. So knock it off!');

    // hide the configs we don't need
    public $remove_configs = array(
        'comments',
        'ealerts',
        'files',
        'rss',
        'aggregation',
        'tags'
    );

    public function edit() {
        $site_page_default = ecomconfig::getConfig('pagination_default');
        assign_to_template(array('site_page_default'=>$site_page_default));
        parent::edit();
    }
    
    function configure() {
        expHistory::set('editable', $this->params);

        // little bit of trickery so that that categories can have their own configs
        
        $this->loc->src = "@store-".$this->params['id'];
        $config = new expConfig($this->loc);
        $this->config = $config->config;
        $pullable_modules = listInstalledControllers($this->classname, $this->loc);
        $views = get_config_templates($this, $this->loc);
        assign_to_template(array('config'=>$this->config, 'pullable_modules'=>$pullable_modules, 'views'=>$views));
    }
    

    function saveconfig() {
        
        // unset some unneeded params
        unset($this->params['module']);
        unset($this->params['controller']);
        unset($this->params['src']);
        unset($this->params['int']);
        unset($this->params['id']);
        unset($this->params['action']);
        unset($this->params['PHPSESSID']);

        // setup and save the config
        $this->loc->src = "@store-".$this->params['cat-id'];
        $config = new expConfig($this->loc);
        $config->update(array('config'=>$this->params));
        flash('message', 'Configuration updated');
        expHistory::back();
    }

    function manage_ranks() {
        global $db;
        $rank = 1;
        $category = new storeCategory($this->params['id']);
        foreach($this->params['rerank'] as $key=>$id) {
            $sql = "SELECT DISTINCT sc.* FROM exponent_product_storeCategories sc JOIN exponent_product p ON p.id = sc.product_id WHERE p.id=".$id." AND sc.storecategories_id IN (SELECT id FROM exponent_storeCategories WHERE rgt BETWEEN ".$category->lft." AND ".$category->rgt.") ORDER BY rank ASC";
            $prod = $db->selectObjectBySQL($sql);
            $prod->rank = $rank;
            $db->updateObject($prod,"product_storeCategories","storecategories_id=".$prod->storecategories_id." AND product_id=".$id);
            $rank += 1;
        }
        
        expHistory::back();
    }
    
    function manage () {
        //         $category = new storeCategory();
        //         $categories = $category->getFullTree();
        //         
        //         // foreach($categories as $i=>$val){
        //         //  if (!empty($this->values) && in_array($val->id,$this->values)) {
        //         //      $this->tags[$i]->value = true;
        //         //  } else {
        //         //      $this->tags[$i]->value = false;
        //         //  }
        //         //  $this->tags[$i]->draggable = $this->draggable; 
        //         //  $this->tags[$i]->checkable = $this->checkable; 
        //         // }
        //         
        //         
        // $obj = json_encode($categories);  
    }
    
    public function update() {
        parent::update();
        $curcat = new storeCategory($this->params);
        $children = $curcat->getChildren();
        foreach ($children as $key=>$child) {
            $chldcat = new storeCategory($child->id);
            $chldcat->is_active = $this->params['is_active'];
            $chldcat->save();
        }
        expHistory::back();
    }
    
    function fix_categories() {
        //--Flat Structure--//
        global $db;
        
        $Nodes = $db->selectArrays('storeCategories');

        //--This function converts flat structure into an array--//
        function BuildTree($TheNodes, $ID = 0, $depth=-1) {
            $Tree = array();
            if(is_array($TheNodes)) {
                
                foreach($TheNodes as $Node) {
                    if($Node["parent_id"] == $ID) {
                        array_push($Tree, $Node);
                    }
                }
                $depth++;
                for($x = 0; $x < count($Tree); $x++) {
                    $Tree[$x]["depth"] = $depth;
                    $Tree[$x]["kids"] = BuildTree($TheNodes, $Tree[$x]["id"], $depth);
                    //array_merge($test,$Tree[$x]["kids"]);
                }
                return($Tree);
            }
        }
        

        //--Call Build Tree (returns structured array)
        $TheTree = BuildTree($Nodes);
        
        
        // flattens a tree created by parent/child relationships
        
        function flattenArray(array $array){
            $ret_array = array();
            $counter=0;
            foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $key=>$value) {
                if ($key=='id') {
                    $counter++;
                }
                $ret_array[$counter][$key] = $value;
            }
            return $ret_array;
        }
        
        // takes a flat array with propper parent/child relationships in propper order
        // and adds the lft and rgt extents correctly for a nested set
        
        function nestify($categories) {
            // Trees mapped            
            $trees = array();
            $trackParents = array();
            $depth=0;
            $counter=1;
            $prevDepth=0;

            foreach ($categories as $key=>$val) {
                if ($counter==1) {
                    # first in loop. We should only hit this once: first.
                    $categories[$key]['lft'] = $counter;
                    $counter++;
                } else if ($val['depth']>$prevDepth) {
                    # we have a child of the previous node
                    $trackParents[] = $key-1;
                    $categories[$key]['lft'] = $counter;
                    $counter++;
                } else if ($val['depth']==$prevDepth) {
                    # we have a sibling of the previous node
                    $categories[$key-1]['rgt'] = $counter;
                    $counter++;
                    $categories[$key]['lft'] = $counter;
                    $counter++;
                } else {
                    # we have moved up in depth, but how far up?
                    $categories[$key-1]['rgt'] = $counter;
                    $counter++;
                    $l=count($trackParents);
                    while($l > 0 && $trackParents[$l - 1]['depth'] >= $val['depth']) {
                        $categories[$trackParents[$l - 1]]['rgt'] = $counter;
                        array_pop($trackParents);
                        $counter++;
                        $l--;
                    }
                    
                    $categories[$key]['lft'] = $counter;
                    $counter++;
                }        
                $prevDepth=$val['depth'];
            }

            $categories[$key]['rgt'] = $counter;
            return $categories;
        }


        
        // takes a flat nested set formatted array and creates a multi-dimensional array from it

        function toHierarchy($collection)
        {
                // Trees mapped
                $trees = array();
                $l = 0;

                if (count($collection) > 0) {
                        // Node Stack. Used to help building the hierarchy
                        $stack = array();

                        foreach ($collection as $node) {
                                $item = $node;
                                $item['children'] = array();

                                // Number of stack items
                                $l = count($stack);

                                // Check if we're dealing with different levels
                                while($l > 0 && $stack[$l - 1]['depth'] >= $item['depth']) {
                                        array_pop($stack);
                                        $l--;
                                }

                                // Stack is empty (we are inspecting the root)
                                if ($l == 0) {
                                        // Assigning the root node
                                        $i = count($trees);
                                        $trees[$i] = $item;
                                        $stack[] = & $trees[$i];
                                } else {
                                        // Add node to parent
                                        $i = count($stack[$l - 1]['children']);
                                        $stack[$l - 1]['children'][$i] = $item;
                                        $stack[] = & $stack[$l - 1]['children'][$i];
                                }
                        }
                }

                return $trees;
        }
        
        // this will test our data manipulation
        // eDebug(toHierarchy(nestify(flattenArray($TheTree))),1);
        
        $flat_fixed_cats = nestify(flattenArray($TheTree));
                
        foreach ($flat_fixed_cats as $k=>$v) {
            $cat = new storeCategory($v['id']);
            $cat->lft = $v['lft'];
            $cat->rgt = $v['rgt'];
            $cat->save();
            eDebug($cat);
        }

        //-Show Array Structure--//
        // print_r($TheTree);
        // 
        // 
        // //--Print the Categories, and send their children to DrawBranch--//
        // //--The code below allows you to keep track of what category you're currently drawing--//
        // 
        // printf("<ul>");
        // 
        // foreach($TheTree as $MyNode) {
        //     printf("<li>{$MyNode['Name']}</li>");
        //     if(is_array($MyNode["Children"]) && !empty($MyNode["Children"])) {
        //         DrawBranch($MyNode["Children"]);
        //     }
        // }
        // printf("</ul>");
        // //--Recursive printer, should draw a child, and any of its children--//
        // 
        // function DrawBranch($Node){
        //     printf("<ul>");
        // 
        //     foreach($Node as $Entity) {
        //         printf("<li>{$Entity['Name']}</li>");
        // 
        //         if(is_array($Entity["Children"]) && !empty($Entity["Children"])) {
        //             DrawBranch($Entity["Children"]);
        //         }
        // 
        //         printf("</ul>");
        //     }
        // }
    }
}

?>
