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

class billingtransaction extends expRecord {  
    public $has_one = array('billingcalculator'); 
	public $table = 'billingtransactions';	
    
    public function getRefNum()
    {
        $opts = expUnserialize($this->billing_options);
        //eDebug($opts);
        return $opts->PNREF; 
    }
}

?>
