{* HEADER *}

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">
      {$form.$elementName.html}
      {if $postHelps.$elementName}<div class="description">{$postHelps.$elementName}</div>{/if}
    </div>
    <div class="clear"></div>
  </div>
  {if $elementName eq 'postal_code'}
    <br/><h2>{ts}Your Information{/ts}</h2>
  {/if}
  {if $elementName eq 'local_chapter'}
    <br/><h2>{ts}Flag Raising Ceremony Details{/ts}</h2>
  {/if}
{/foreach}

{if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
