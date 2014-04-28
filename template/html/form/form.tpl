<form action="{route _name=$route arguments=$routeContext}" method="post" enctype="multipart/form-data">
    {foreach from=$fieldsets item=set}
        <fieldset class="block">
            <legend><span>{$set.name|escape}</span></legend>
            <ul>
                {foreach from=$set.rows item=row}
                    {$class=$row->getClass()}
                    <li{if not empty($class)} class="{$class}"{/if}>
                        {foreach from=$row->getElements() item=element}
                            {$element->render()}
                        {/foreach}
                    </li>
                {/foreach}

                {* add buttons *}
                {if not empty($set.buttons)}
                    {strip}
                    <li class="labelWrapper">
                        {foreach from=$set.buttons item=button}
                            {include file='form/form_button.tpl' button=$button}
                        {/foreach}
                    </li>
                    {/strip}
                {/if}
            </ul>
        </fieldset>
    {/foreach}
</form>