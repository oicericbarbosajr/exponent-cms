<?php

##################################################
#
# Copyright (c) 2004-2008 OIC Group, Inc.
# Written and Designed by Adam Kessler
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

class flowplayerController extends expController {
    //public $basemodel_name = '';
    public $useractions = array('showall'=>'Show all');

	public $remove_configs = array(
        'aggregretion',
        'comments',
        'files',
        'rss',
        'tags'
    );

    function name() { return $this->displayname(); } //for backwards compat with old modules
    function displayname() { return "Flowplayer Media Player"; }
    function description() { return "Flowplayer is a video player for Web sites. Use it to embed video streams into your HTML pages."; }
    function author() { return "Adam Kessler - OIC Group, Inc"; }
    function hasSources() { return true; }
    function hasViews() { return true; }
    function hasContent() { return true; }
    function supportsWorkflow() { return false; }
    function isSearchable() { return true; }
    
    function showall() {
        expHistory::set('viewable', $this->params);
        $modelname = $this->basemodel_name;
        $where = $this->hasSources() ? $this->aggregateWhereClause() : null;
        $limit = isset($this->params['limit']) ? $this->params['limit'] : null;
        $order = "rank";
        $page = new expPaginator(array(
                    'model'=>$modelname,
                    'where'=>$where, 
                    'limit'=>$limit,
                    'order'=>$order,
                    'controller'=>$this->baseclassname,
                    'action'=>$this->params['action'],
                    'columns'=>array('ID#'=>'id','Title'=>'title', 'Body'=>'body'),
                    ));
        
        assign_to_template(array('page'=>$page, 'items'=>$page->records, 'modelname'=>$modelname));
    }

    
}

?>
