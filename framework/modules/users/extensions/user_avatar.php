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

class user_avatar extends expRecord {
#    public $validates = array(
#        'presence_of'=>array(
#            'title'=>array('message'=>'Title is a required field.'),
#            'body'=>array('message'=>'Body is a required field.'),
#        ));
        
    public function name() { return 'Avatars'; }
	public function description() { return 'The extension allows users to upload avatar images.'; }

    public function update($params=array()) {
        global $db;
        
        // if not user id then we should not be doing anything here
        if (empty($params['user_id'])) return false;
        $this->user_id = $params['user_id'];
        
        // check for a previous avatar otherwise set the default
        $this->image = $params['current_avatar'];
        if (empty($this->image)) $this->image = URL_FULL.'framework/modules/users/avatars/avatar_not_found.jpg';
        
        // if the user uploaded a new avatar lets save it!
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $info = expFile::getImageInfo($_FILES['avatar']['tmp_name']);
            if ($info['is_image']) {
                // figure out the mime type and set the file extension and name
                $extinfo = split('/',$info['mime']);
                $extension = $extinfo[1];
                $avatar_name = $this->user_id.'.'.$extension;
                
                // save the file to the filesystem
                $file = expFile::fileUpload('avatar', true, false, $avatar_name, 'framework/modules/users/avatars/');
                
                //save the file to the database                
                $this->image = $file->url;
            }
        }
        
        parent::update();
    }	
}

?>
