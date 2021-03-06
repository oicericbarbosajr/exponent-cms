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

<div class="module administration manage-unused-tables">
    <h1>Deprecated/Unused Tables</h1>
    <h2>{$unused_tables|@count} unused tables found</h2>
    <p>
        The list of tables below are ones that are no long used by Exponent. These tables probably
        aren't hurting anything.  If you do not have a good idea of what a table does or why it is there
        it is probably best to just leave it.
    </p>
    
    {form action=delete_unused_tables}
        <table class="exp-skin-table">
        <thead>
            <tr>
                <th>Delete?</th>
                <th>Table Name</th>
                <th># Rows</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$unused_tables item=table key=basename}
            <tr class="{cycle values="even, odd"}">
                <td>{control type="checkbox" name="tables[]" label=" " value=$table->name checked=1}</td>
                <td>{$basename}</td>
                <td>{$table->rows}</td>
            </tr>
            {foreachelse}
            <tr><td>No unused tables were found.</td></tr>
            {/foreach}
        </tbody>
        </table>
        {control type="buttongroup" submit="Delete Tables" cancel="Cancel"}
    {/form}
</div>
