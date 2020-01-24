{* HEADER *}
{crmScope extensionKey='biz.jmaconsulting.raisetheflagforautism'}
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">
      {$form.$elementName.html}
      {if $postHelps.$elementName}<div class="description">{$postHelps.$elementName}</div>{/if}
    </div>
    <div class="clear"></div>
  </div>
  {if $elementName eq 'attending'}
    <br/><br/><u><h4>{ts}Your Information{/ts}</h4></u>
    <div class="description">{ts}(Will not be shared with the public - will be used to verify ceremony and send flag as required){/ts}</div><br/>
  {/if}
  {if $elementName eq 'local_chapter'}
    <br/><br/><u><h4>{ts}Flag Raising Ceremony Details{/ts}</h4></u>
  {/if}
{/foreach}

{if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/crmScope}
