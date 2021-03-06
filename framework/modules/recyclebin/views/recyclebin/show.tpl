{*
 * Copyright (c) 2004-2008 OIC Group, Inc.
 * Written and Designed by Phillip Ball
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

<html>
<head>
    <title>Restore from Recycle Bin</title>
    <link rel="stylesheet" type="text/css" href="{$smarty.const.URL_FULL}tmp/css/exp-styles-min.css" >
    <link rel="stylesheet" type="text/css" href="{$smarty.const.URL_FULL}framework/modules/recyclebin/css/recyclebin.css" >
    
</head>
<body>
    <div class="recyclebin orphan-content">
        <h1 class="main">Recycle Bin - {$module|getControllerName|capitalize} items</h1>
        {foreach from=$items item=item}
            <div class="rb-item">
                <a class="usecontent" href="#" onclick="window.opener.EXPONENT.useRecycled('{$item->source}');window.close();">
                    Restore this content
                </a>
                <div class="recycledcontent">
                    {$item->html}
                </div>     
            </div>
        {foreachelse}
            <div class="rb-item">
                There's nothing for this module that's been sent the the Recycle Bin.
            </div>
        {/foreach}
    </div>
</body>
</html>
