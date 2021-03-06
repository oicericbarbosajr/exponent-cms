{*
 * Copyright (c) 2004-2008 OIC Group, Inc.
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

<div class="module motd edit">
    <h1>{if $record->id == ''}New Message of the Day{else}Edit Message of the Day{/if}</h1>
    <p>{$record->body}</p>
    
    {form action='update'}
        {control type="hidden" name="id" value=$record->id}
        {control type="text" name="body" label="Message" size=35 value=$record->body}
        {control type="dropdown" name="month" label="Month" items=$record->months value=$record->month}
        {control type="dropdown" name="day" label="Day" from=1 to=31 value=$record->day}
        {control type="buttongroup" submit="Submit" cancel="Cancel"}
    {/form}
</div>
