{**
 * @param $type button type
 * @param $label text on the button
 *}
<p class="asOuterLabel">
    <button class="{$button.type}" type="{$button.type}"{if isset($button.name)} name="{$button.name|escape}" value="{$button.value|escape}"{/if}>
        <span>{$button.label|escape}</span>
    </button>
</p>
