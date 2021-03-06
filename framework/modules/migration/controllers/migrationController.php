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

class migrationController extends expController {
    //public $basemodel_name = '';
    protected $permissions = array('manage'=>'Manage', 'analyze'=>'Analyze Data', 'migrate'=>'Migrate Data','configure'=>'Configure');
    //public $useractions = array('showall'=>'Show all');
	public $useractions = array();
	public $codequality = 'alpha';
    
    // this is a list of modules that we can convert to exp2 type modules.
    public $new_modules = array(
        'textmodule'=>'textController',
        'newsmodule'=>'newsController',
        'resourcesmodule'=>'filedownloadController',
        'addressbookmodule'=>'addressController',
        'slideshowmodule'=>'photosController',
        'imagegallerymodule'=>'photosController',
        'linklistmodule'=>'linksController',
        'snippetmodule'=>'snippetController',
        'swfmodule'=>'textController',
        'rotatormodule'=>'textController'
    );
    
    // these are modules that have either been deprecated or have no content to migrate
    // Not sure we need to note deprecated modules...
    public $deprecated_modules = array(
        'administrationmodule',
        'contactmodule',
        'containermodule',
        'searchmodule',
        'rssmodule',
        'navigationmodule',
        'imagemanagermodule',
        'imageworkshopmodule',
        'inboxmodule',
        'previewmodule',
        'loginmodule'
    );

    public $needs_written = array(
        'formmodule',
        'listingmodule',
    );
    
    function name() { return $this->displayname(); } //for backwards compat with old modules
    function displayname() { return "Content Migration Controller"; }
    function description() { return "Use this module to pull Exponent 1 style content from your old site."; }
    function author() { return "Adam Kessler - OIC Group, Inc"; }
    function hasSources() { return false; }
    function hasViews() { return true; }
    function hasContent() { return false; }
    function supportsWorkflow() { return false; }
    function isSearchable() { return false; }
    
    public function analyze_site() {
        global $db;
        //$containers = $db->selectObjects('container', 'external="N;"');
        //eDebug($containers);
        $old_db = $this->connect();

        $sql  = 'SELECT *, COUNT(module) as count FROM '.$this->config['prefix'].'_sectionref WHERE is_original=1 GROUP BY module';
        $modules = $old_db->selectObjectsBySql($sql);
        for($i=0; $i<count($modules); $i++) {
            if (array_key_exists($modules[$i]->module, $this->new_modules)) {
                $newmod = new $this->new_modules[$modules[$i]->module]();
                $modules[$i]->action = '<span style="color:green;">Converting content to '.$newmod->displayname()."</span>";
            } elseif (in_array($modules[$i]->module, $this->deprecated_modules)) {
                // $modules[$i]->action = '<span style="color:red;">This module is deprecated and will not be migrated.</span>';
                $modules[$i]->notmigrating = 1;
            } elseif (in_array($modules[$i]->module, $this->needs_written)) {
                $modules[$i]->action = '<span style="color:orange;">Still needs migration script written</span>';
            } else {
                $modules[$i]->action = 'Migrating as is.';
            }
        }
        //eDebug($modules);
        
        assign_to_template(array('modules'=>$modules));
    }
    
    public function migrate_files() {
        global $db;
        
        $old_db = $this->connect();
        $db->delete('expFiles');
        echo "<ol>";
        
        echo "<li class=\"mig-msg\">
            Emptied expFiles table before file import.
        </li>";
        
        // // pull the sectionref data
        // $secref = $old_db->selectObjects('sectionref');
        // foreach ($secref as $sr) {
        //     if (array_key_exists($sr->module, $this->new_modules)) {
        //         $sr->module = $this->new_modules[$sr->module];
        //         $db->insertObject($sr, 'sectionref');
        //     } elseif (in_array($sr->module, $this->deprecated_modules)) {
        //         // do nothing...we don't want this module
        //     } else {
        //         $db->insertObject($sr, 'sectionref');
        //     }            
        // }
        // 
        echo "<li class=\"mig-msg\">
            Importing files
        </li>";
        
        //import the files
        $oldfiles = $old_db->selectObjects('file');
        foreach ($oldfiles as $oldfile) {
            unset($oldfile->name);
            unset($oldfile->collection_id);
            $file = $oldfile;
            $file->directory = $file->directory."/";
            $db->insertObject($file,'expFiles');
        }
        
        echo "<li class=\"mig-msg\">
            ".count($oldfiles)." files imported.
        </li>";

        echo "<li class=\"mig-msg\">
            Done! You should now have all files from your previous system in your file manager.
        </li>";
        
        echo "</ol>";

    }
    
    public function migrate_content() {
        global $db;
        
        $old_db = $this->connect();
        if (isset($this->params['wipe_content'])) {
            $db->delete('sectionref');
            $db->delete('locationref');
            $db->delete('container');
            $db->delete('text');
            $db->delete('snippet');
            $db->delete('news');
            $db->delete('links');
            $db->delete('photo');
            $db->delete('filedownloads');
            $db->delete('content_expFiles');
            $db->delete('calendar');
            $db->delete('calendarmodule_config');
            $db->delete('eventdate');
            @$this->msg['clearedcontent']++;
        }
        
        //pull the locationref data
        $where = '1';
        foreach ($this->params['migrate'] as $key=>$var) {
            $where .= " and module='".$key."'";
        }

        $locref = $old_db->selectObjects('locationref',$where);
        foreach ($locref as $lr) {
            if (array_key_exists($lr->module, $this->new_modules)) {
                $lr->module = $this->new_modules[$lr->module];
            }
            
            if (!in_array($lr->module, $this->deprecated_modules)) {
                if (!$db->selectObject('locationref',"source='".$lr->source."'")) {
                    $db->insertObject($lr, 'locationref');
                    @$this->msg['locationref']++;
                }
            }
        }

        // pull the sectionref data
        $secref = $old_db->selectObjects('sectionref',$where);
        foreach ($secref as $sr) {
            // hard coded modules
            if (array_key_exists($sr->module, $this->new_modules) && ($sr->refcount==1000)) {
                $iloc->mod = $sr->module;
                $iloc->src = $sr->source;
                $iloc->int = $sr->internal;
                $this->convert($iloc,$iloc->mod,1);                
                
                // convert the source to new exp controller
                $sr->module = $this->new_modules[$sr->module];            
            }
            
            if (!in_array($sr->module, $this->deprecated_modules)) {
                // if the module is not in the depecation list, we're hitting here
                if (!$db->selectObject('sectionref',"source='".$sr->source."'")) {
                    $db->insertObject($sr, 'sectionref');
                    @$this->msg['sectionref']++;
                }
            }
        }

        //pull over all the container modules
        $containers = $old_db->selectObjects('container', 'external="N;"');
        foreach ($containers as $cont) {
            if (!$db->selectObject('container',"internal='".$cont->internal."'")) {
                $db->insertObject($cont, 'container');
                @$this->msg['container']++;
            }
        }

        // echo "Imported containermodules<br>";
        // 
        // // this will pull all the old modules.  if we have a exp2 equivalent module
        // // we will convert it to the new type of module before pulling.
        $cwhere = ' and (';
        $i=0;
        foreach ($this->params['migrate'] as $key=>$var) {
            $cwhere .= ($i==0) ? "" : " or ";
            $cwhere .= "internal like '%".$key."%'";
            $i=1;
        }
        $cwhere .= ")";
        $modules = $old_db->selectObjects('container', 'external != "N;"'.$cwhere);
        foreach($modules as $module) {
            $iloc = expUnserialize($module->internal);
            if (array_key_exists($iloc->mod, $this->new_modules)) {
                // convert new modules added via container
                unset($module->internal);
                unset($module->action);
                unset($module->view);
                $this->convert($iloc, $module);                
            } else if (!in_array($iloc->mod, $this->deprecated_modules)) {
                // add old school modules not in the deprecation list
                if (!$db->selectObject('container',"internal='".$cont->internal."'")) {
                    $db->insertObject($module, 'container');
                    @$this->msg['container']++;
                }
                $this->pulldata($iloc, $module);
            } 
        }
        
        expSession::clearUserCache();
        assign_to_template(array('msg'=>@$this->msg));
    }
    
    private function pulldata($iloc, $module) {
        global $db;
        $old_db = $this->connect();
        
        switch ($iloc->mod) {
            case 'calendarmodule':
                $events = $old_db->selectObjects('eventdate', "location_data='".serialize($iloc)."'");
                foreach($events as $event) {
                    $db->insertObject($event, 'eventdate');
                }
                $cals = $old_db->selectObjects('calendar', "location_data='".serialize($iloc)."'");
                foreach($cals as $cal) {
                    unset($cal->allow_registration);
                    unset($cal->registration_limit);
                    unset($cal->registration_allow_multiple);
                    unset($cal->registration_cutoff);
                    unset($cal->registration_price);
                    unset($cal->registration_count);
                    $db->insertObject($cal, 'calendar');
                }
                $configs = $old_db->selectObjects('calendarmodule_config', "location_data='".serialize($iloc)."'");
                foreach ($configs as $config) {
                    $db->insertObject($config, 'calendarmodule_config');
                }
            break;
        }
    }
    
    private function add_container($iloc,$m) {
        global $db;
        $iloc->mod = $this->new_modules[$iloc->mod];
        $m->internal = (isset($m->internal) && strstr($m->internal,"Controller")) ? $m->internal : serialize($iloc);
        $m->action = isset($m->action) ? $m->action : 'showall';
        $m->view = isset($m->view) ? $m->view : 'showall';
        $db->insertObject($m, 'container');
    }
    
    private function convert($iloc, $module, $hc=0) {
        if (!array_key_exists($iloc->mod, $this->params['migrate'])) return $module;
        global $db;
        $old_db = $this->connect();
        
        switch ($iloc->mod) {
            case 'textmodule':
                $iloc->mod = 'textmodule';
                $textitems = $old_db->selectObjects('textitem', "location_data='".serialize($iloc)."'");

                if ($textitems) {
                    foreach ($textitems as $ti) {
                        $text = new text();
                        $loc = expUnserialize($ti->location_data);
                        $loc->mod = "text";
                        $text->location_data = serialize($loc);
                        $text->body = $ti->text;

                        $text->save();
                        @$this->msg['migrated'][$iloc->mod]['count']++;
                        @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                    }
                }
            break;
            case 'rotatormodule':

                // quick check for hard coded modules
                $module->action = 'showRandom';
                $module->view = 'showRandom';                
                
                $iloc->mod = 'rotatormodule';
                $textitems = $old_db->selectObjects('rotator_item', "location_data='".serialize($iloc)."'");
                if ($textitems) {
                    foreach ($textitems as $ti) {
                        $text = new text();
                        $loc = expUnserialize($ti->location_data);
                        $loc->mod = "text";
                        $text->location_data = serialize($loc);
                        $text->body = $ti->text;

                        $text->save();
                        @$this->msg['migrated'][$iloc->mod]['count']++;
                        @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                    }
                }
            break;
            case 'snippetmodule':
                $iloc->mod = 'snippetmodule';
                $textitems = $old_db->selectObjects('textitem', "location_data='".serialize($iloc)."'");
                if ($textitems) {
                    foreach ($textitems as $ti) {
                        $text = new snippet();
                        $loc = expUnserialize($ti->location_data);
                        $loc->mod = "snippet";
                        $text->location_data = serialize($loc);
                        $text->body = $ti->text;
                        // if the item exists in the current db, we won't save it
                        $te = $text->find('first',"location_data='".$text->location_data."'");
                        if (empty($te)) {
                            $text->save();
                            @$this->msg['migrated'][$iloc->mod]['count']++;
                            @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                        }
                    }
                }
            break;
            case 'linklistmodule':
                $iloc->mod = 'linklistmodule';
                $links = $old_db->selectArrays('linklist_link', "location_data='".serialize($iloc)."'");

                @$module->view = "showall_quicklinks";

                foreach ($links as $link) {
                    $lnk = new links();
                    $loc = expUnserialize($link['location_data']);
                    $loc->mod = "links";
                    $lnk->title = $link['name'];
                    $lnk->body = $link['description'];
                    $lnk->new_window = $link['opennew'];
                    $lnk->url = $link['url'];
                    $lnk->rank = $link['rank'];                    
                    $lnk->poster = 1;
                    $lnk->editor = 1;                    
                    $lnk->location_data = serialize($loc);
                    
                    $lnk->save();
                    @$this->msg['migrated'][$iloc->mod]['count']++;
                    @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                }
            break;
            case 'swfmodule':
                $iloc->mod = 'swfmodule';
                $swfitems = $old_db->selectObjects('swfitem', "location_data='".serialize($iloc)."'");
                foreach ($swfitems as $ti) {
                    $text = new text();
                    $file = new expFile($ti->swf_id);
                    $loc = expUnserialize($ti->location_data);
                    $loc->mod = "text";
                    $text->location_data = serialize($loc);
                    $text->title = $ti->name;
                    $swfcode = '
                        <p>
                         <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" height="'.$ti->height.'" width="'.$ti->width.'">
                             <param name="bgcolor" value="'.$ti->bgcolor.'" />
                                '.($ti->transparentbg?"<param name=\"wmode\" value=\"transparent\" />":"").'
                             <param name="quality" value="high" />
                             <param name="movie" value="'.$file->path_relative.'" />
                             <embed bgcolor= "'.$ti->bgcolor.'" pluginspage="http://www.macromedia.com/go/getflashplayer" quality="high" src="'.$file->path_relative.'" type="application/x-shockwave-flash" height="'.$ti->height.'" width="'.$ti->width.'"'.($ti->transparentbg?" wmode=\"transparent\"":"").'>
                             </embed>
                         </object>
                        </p>    
                    ';
                    $text->body = $swfcode;
                    $text->save();
                    @$this->msg['migrated'][$iloc->mod]['count']++;
                    @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                }
            break;
            case 'newsmodule':                
                $iloc->mod = 'newsmodule';
                $newsitems = $old_db->selectArrays('newsitem', "location_data='".serialize($iloc)."'");
                
                if ($newsitems) {
                    foreach ($newsitems as $ni) {
                        unset($ni['id']);
                        $news = new news($ni);                   
                        $loc = expUnserialize($ni['location_data']);
                        $loc->mod = "news";
                        $news->location_data = serialize($loc);
                        $news->created_at = $ni['posted'];
                        $news->edited_at = $ni['edited'];                    

                        $news->save();
                        @$this->msg['migrated'][$iloc->mod]['count']++;
                        @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                        if (!empty($ni['file_id'])) {
                            $oldfile = $old_db->selectArray('file', 'id='.$ni['file_id']);
                            $file = new expFile($oldfile);
                            $news->attachitem($file,'downloadable');
                        }
                    }
                }
            break;
            case 'resourcesmodule':
                $iloc->mod = 'resourcesmodule';
                $resourceitems = $old_db->selectArrays('resourceitem', "location_data='".serialize($iloc)."'");
                foreach ($resourceitems as $ri) {
                    unset($ri['id']);
                    $filedownload = new filedownload($ri);                   
                    $loc = expUnserialize($ri['location_data']);
                    $loc->mod = "filedownload";
                    $filedownload->title = $ri['name'];
                    $filedownload->body = $ri['description'];
                    $filedownload->downloads = $ri['num_downloads'];
                    $filedownload->location_data = serialize($loc);
                    $filedownload->created_at = $ri['posted'];
                    $filedownload->edited_at = $ri['edited']; 

                    $filedownload->save();
                    @$this->msg['migrated'][$iloc->mod]['count']++;
                    @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];

                    if (!empty($ri['file_id'])) {
                        $oldfile = $old_db->selectArray('file', 'id='.$ri['file_id']);
                        $file = new expFile($oldfile);
                        $filedownload->attachitem($file,'downloadable');
                    }
                }
            break;
            case 'imagegallerymodule':
                $iloc->mod = 'imagegallerymodule';
                $galleries = $old_db->selectArrays('imagegallery_gallery', "location_data='".serialize($iloc)."'");
                foreach ($galleries as $gallery) {
                    $gis = $old_db->selectArrays('imagegallery_image', "gallery_id='".$gallery['id']."'");
                    //eDebug($gis,1);
                    foreach ($gis as $gi) {
                        $photo = new photo();                   
                        //$loc = expUnserialize($gi['location_data']);
                        $loc->mod = "photos";
                        $photo->title = $gi['name'];
                        $photo->body = $gi['description'];
                        $photo->alt = $gi['alt'];
                        $photo->location_data = serialize($iloc);

                        $photo->save();
                        @$this->msg['migrated'][$iloc->mod]['count']++;
                        @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                        if (!empty($gi['file_id'])) {
                            $file = new expFile($gi['file_id']);
                            $photo->attachitem($file,'');
                        }
                    }
                }
            break;
            case 'slideshowmodule':
                $iloc->mod = 'slideshowmodule';
                $galleries = $old_db->selectArrays('imagegallery_gallery', "location_data='".serialize($iloc)."'");
                foreach ($galleries as $gallery) {
                    $gis = $old_db->selectArrays('imagegallery_image', "gallery_id='".$gallery['id']."'");
                    //eDebug($gis,1);
                    foreach ($gis as $gi) {
                        $photo = new photo();                   
                        //$loc = expUnserialize($gi['location_data']);
                        $loc->mod = "photos";
                        $photo->title = $gi['name'];
                        $photo->body = $gi['description'];
                        $photo->alt = $gi['alt'];
                        $photo->location_data = serialize($iloc);
                        // $photo->created_at = $gi['posted'];
                        // $photo->edited_at = $gi['edited'];                    

                        $te = $photo->find('first',"location_data='".$photo->location_data."'");
                        if (empty($te)) {
                            $photo->save();
                            @$this->msg['migrated'][$iloc->mod]['count']++;
                            @$this->msg['migrated'][$iloc->mod]['name'] = $this->new_modules[$iloc->mod];
                            if (!empty($gi['file_id'])) {
                                $file = new expFile($gi['file_id']);
                                $photo->attachitem($file,'');
                            }
                        }

                    }
                }
            break;
            default:
                @$this->msg['noconverter'][$iloc->mod]++;
            break;
        }
        
        // quick check for non hard coded modules
        // We add a container if they're not hard coded.
        (!$hc) ? $this->add_container($iloc,$module) : "";
        
        return $module;
    }
    
    
    
    public function manage_pages() {
        expHistory::set('managable', $this->params);
        $old_db = $this->connect();
        $pages = $old_db->selectObjects('section');
        assign_to_template(array('pages'=>$pages));
    }
    
    public function manage_files() {
        expHistory::set('managable', $this->params);
        $old_db = $this->connect();
        $files = $old_db->selectObjects('file');
        assign_to_template(array('files'=>$files));
    }
    
    public function migrate_pages() {
        global $db;
        
        $successful = 0;
        $failed     = 0;
        $old_db = $this->connect();
        foreach($this->params['pages'] as $pageid) {
            $page = $old_db->selectObject('section', 'id='.$pageid);
            $ret = $db->insertObject($page, 'section');
            if (empty($ret)) {
                $failed += 1;
            } else {
                $successful += 1;
            }
        }
        
        flash ('message', $successful.' pages were imported from '.$this->config['database']);
        if ($failed > 0) {
            flash('error', $failed.' pages could not be imported from '.$this->config['database'].' This is usually because a page with the same ID already exists in the database you importing to.');
        }
        
        expSession::clearUserCache();
        expHistory::back();
    }
    
    private function connect() {
        // check for required info...then make the DB connection.
        if (
            empty($this->config['username']) || 
            empty($this->config['password']) ||
            empty($this->config['database']) ||
            empty($this->config['server']) ||
            empty($this->config['prefix']) ||
            empty($this->config['port'])
        ) {
            flash ('error', 'You are missing some required database connectin information.  Please enter DB information.');
            redirect_to (array('controller'=>'migration', 'action'=>'configure'));
        }
        
       $database = exponent_database_connect($this->config['username'],$this->config['password'],$this->config['server'].':'.$this->config['port'],$this->config['database']);
       
       if (empty($database->havedb)) {
            flash ('error', 'An error was encountered trying to connect to the database you specified. Please check your DB config.');
            redirect_to (array('controller'=>'migration', 'action'=>'configure'));
       } 
       
       $database->prefix = $this->config['prefix']. '_';;
       return $database;
    }
}

?>
