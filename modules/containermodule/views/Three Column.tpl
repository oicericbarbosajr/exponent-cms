{*
 * Copyright (c) 2004-2006 OIC Group, Inc.
 * Written and Designed by James Hunt
 *
 * This file is part of Exponent
 *
 * Exponent is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * GPL: http://www.gnu.org/licenses/gpl.txt
 *
 *}
 
{css unique="3col-container" link="`$smarty.const.PATH_RELATIVE`framework/modules/container/assets/css/container.css"}

{/css}
 
<div class="containermodule three-column">
    {viewfile module=$singlemodule view=$singleview var=viewfile}
    <div class="col1">
    	{assign var=container value=$containers.0}
    	{assign var=rank value=0}
    	{include file=$viewfile}
        <div style="clear:both"></div>
    </div>
    <div class="col2">
    	{assign var=container value=$containers.1}
    	{assign var=rank value=1}
    	{include file=$viewfile}
        <div style="clear:both"></div>
    </div>
    <div class="col3">
    	{assign var=container value=$containers.2}
    	{assign var=rank value=2}
    	{include file=$viewfile}
        <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
</div>
