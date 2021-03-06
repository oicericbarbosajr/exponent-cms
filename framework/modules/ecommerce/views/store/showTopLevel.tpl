{*
 * Copyright (c) 2007-2008 OIC Group, Inc.
 * Written and Designed by Adam Kessler
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

<div class="module store showall-toplevel">
	{assign var=depth value=0}
	{if $moduletitle}<h2>{$moduletitle}</h2>{/if}
	<div id="catnav">
		<ul>	
			{foreach from=$categories item=category}
    			{if $category->is_active==1 || $user->is_acting_admin}
				<li class="{if $topcat->id==$category->id}current{/if}{if $category->is_active!=1} inactive{/if}">
					<a href="{link controller=store action=showall title=$category->sef_url}">{$category->title} <span class="productsincategory">{$category->product_count}</span></a>
				</li>
				{/if}
			{/foreach}
		</ul>
	</div>
</div>
