<{$tag}{foreach from=$attributes key=attribute item=value} {$attribute}="{$value|escape}"{/foreach}{if $standalone} />{else}>{strip}
{/strip}{if $escapeInnerHtml}{$innerHTML|escape}{else}{$innerHTML}{/if}{strip}
</{$tag}>
{/strip}{/if}
