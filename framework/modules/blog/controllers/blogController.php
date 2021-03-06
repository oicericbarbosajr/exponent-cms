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

class blogController extends expController {
    //public $basemodel_name = '';
    public $useractions = array(
        'showall'=>'Show all', 
        'tags'=>"Tags",
        'authors'=>"Authors",
        'dates'=>"Dates",
    );
    
    public $remove_configs = array('ealerts','tags','files');
    public $codequality = 'beta';
    

    function name() { return $this->displayname(); } //for backwards compat with old modules
    function displayname() { return "Blog"; }
    function description() { return "This module allows you to run a blog on your site."; }
    function author() { return "Adam Kessler, Phillip Ball - OIC Group, Inc"; }
    function hasSources() { return true; }
    function hasViews() { return true; }
    function hasContent() { return true; }
    function supportsWorkflow() { return false; }
    function isSearchable() { return true; }
    
    public function showall() {
	    expHistory::set('viewable', $this->params);
		$where = $this->aggregateWhereClause();
		$order = 'created_at';
		$limit = empty($this->config['limit']) ? 10 : $this->config['limit'];
		$dir = empty($this->config['sort_dir']) ? 'DESC' : $this->config['sort_dir'];
		
		$page = new expPaginator(array(
		            'model'=>'blog',
		            'where'=>$where, 
		            'limit'=>$limit,
		            'src'=>$this->loc->src,
		            'order'=>$order,
		            'dir'=>$dir,
		            'controller'=>$this->baseclassname,
		            'action'=>$this->params['action'],
		            'columns'=>array('Title'=>'title'),
		            ));
		            
		assign_to_template(array('page'=>$page));
	}
	
	public function tags() {
        $blogs = $this->blog->find('all');
        $used_tags = array();
        foreach ($blogs as $blog) {
            foreach($blog->expTag as $tag) {
                if (isset($used_tags[$tag->id])) {
                    $used_tags[$tag->id]->count += 1;
                } else {
                    $exptag = new expTag($tag->id);
                    $used_tags[$tag->id] = $exptag;
                    $used_tags[$tag->id]->count = 1;
                }
                
            }
        }
        
        $used_tags = expSorter::sort(array('array'=>$used_tags,'sortby'=>'title', 'order'=>'ASC', 'ignore_case'=>true));
	    assign_to_template(array('tags'=>$used_tags));
	}
	
	public function authors() {
        $blogs = $this->blog->find('all');
        $users = array();
        foreach ($blogs as $blog) {
            if (isset($users[$blog->poster])) {
                $users[$blog->poster]->count += 1;
            } else {
                $users[$blog->poster] = new user($blog->poster);
                $users[$blog->poster]->count = 1;
            }
            
        }
        
	    assign_to_template(array('authors'=>$users));
	}
	
	public function dates() {
	    global $db;
	    $dates = $db->selectColumn('blog', 'created_at', $this->aggregateWhereClause());
	    $blog_dates = array();
	    foreach ($dates as $date) {
	        $year = date('Y',$date);
	        $month = date('n',$date);
	        if (isset($blog_date[$year][$month])) {
	            $blog_date[$year][$month]->count += 1;
	        } else {
	            $blog_date[$year][$month]->name = date('F',$date);
	            $blog_date[$year][$month]->count = 1;    
	        }   
	    }
	    ksort($blog_date);
	    $blog_date = array_reverse($blog_date,1);
	    foreach ($blog_date as $key=>$val) {
    	    ksort($blog_date[$key]);
    	    $blog_date[$key] = array_reverse($blog_date[$key],1);
	    }
	    //eDebug($blog_date);
	    assign_to_template(array('dates'=>$blog_date));
	}
	
	public function showall_by_date() {
	    expHistory::set('viewable', $this->params);
	    
	    $start_date = mktime(0, 0, 0, $this->params['month'], 1, $this->params['year']);
	    $end_date = mktime(0, 0, 0, $this->params['month']+1, 0, $this->params['year']);
		$where = $this->aggregateWhereClause().' AND created_at > '.$start_date.' AND created_at < '.$end_date;
		$order = 'created_at';
		$limit = empty($this->config['limit']) ? 10 : $this->config['limit'];
		
		$page = new expPaginator(array(
		            'model'=>'blog',
		            'where'=>$where, 
		            'limit'=>$limit,
		            'order'=>$order,
		            'controller'=>$this->baseclassname,
		            'action'=>$this->params['action'],
		            'columns'=>array('Title'=>'title'),
		            ));
		            
		assign_to_template(array('page'=>$page));
	}
	
	public function showall_by_author() {
	    expHistory::set('viewable', $this->params);
	    
	    $user = user::getByUsername($this->params['author']);
	    
		$where = $this->aggregateWhereClause()." AND poster=".$user->id;
		$order = 'created_at';
		$limit = empty($this->config['limit']) ? 10 : $this->config['limit'];
		
		$page = new expPaginator(array(
		            'model'=>'blog',
		            'where'=>$where, 
		            'limit'=>$limit,
		            'order'=>$order,
		            'controller'=>$this->baseclassname,
		            'action'=>$this->params['action'],
		            'columns'=>array('Title'=>'title'),
		            ));
		            
		assign_to_template(array('page'=>$page));
	}
	
	public function showall_by_tags() {
	    global $db;	    

	    // set history
	    expHistory::set('viewable', $this->params);
	    
	    // get the tag being passed
        $tag = new expTag($this->params['tag']);

        // find all the id's of the blog posts for this blog module
        $blog_ids = $db->selectColumn('blog', 'id', $this->aggregateWhereClause());
        
        // find all the blogs that this tag is attached to
        $blogs = $tag->findWhereAttachedTo('blog');
        
        // loop the blogs for this tag and find out which ones belong to this module
        $blogs_by_tags = array();
        foreach($blogs as $blog) {
            if (in_array($blog->id, $blog_ids)) $blogs_by_tags[] = $blog;
        }

        // create a pagination object for the blog posts and render the action
		$order = 'created_at';
		$limit = empty($this->config['limit']) ? 10 : $this->config['limit'];
		
		$page = new expPaginator(array(
		            'records'=>$blogs_by_tags,
		            'limit'=>$limit,
		            'order'=>$order,
		            'controller'=>$this->baseclassname,
		            'action'=>$this->params['action'],
		            'columns'=>array('Title'=>'title'),
		            ));
		
		assign_to_template(array('page'=>$page));
	}
	
	public function show() {
	    global $template;	    
	    expHistory::set('viewable', $this->params);
	    $id = isset($this->params['title']) ? $this->params['title'] : $this->params['id'];
	    $blog = new blog($id);
	    
	    // since we are probably getting here via a router mapped url
	    // some of the links (tags in particular) require a source, we will
	    // populate the location data in the template now.
	    $loc = expUnserialize($blog->location_data);
	    
	    assign_to_template(array('__loc'=>$loc,'record'=>$blog));
	}
	
	public function update() {
	    //FIXME:  Remove this code once we have the new tag implementation	    
	    if (!empty($this->params['tags'])) {
	        global $db;
	        if (isset($this->params['id'])) {
    	        $db->delete('content_expTags', 'content_type="blog" AND content_id='.$this->params['id']);
    	    }
    	    
	        $tags = explode(",", $this->params['tags']);
	        
	        foreach($tags as $tag) {
	            $tag = trim($tag);
	            $expTag = new expTag($tag);
	            if (empty($expTag->id)) $expTag->update(array('title'=>$tag));
	            $this->params['expTag'][] = $expTag->id;
	        }
	    }
	    // call expController update to save the blog article
	    parent::update();
	}
}
?>