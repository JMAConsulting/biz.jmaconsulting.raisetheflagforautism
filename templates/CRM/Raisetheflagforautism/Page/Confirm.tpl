<h2>'{$event.title}' created succesfully</h2>

<h3>{ts}Thanks for submitting this event. We will review your submission and add it to the website shortly.{/ts}</h3>

<div class="vevent crm-event-id-{$event.id} crm-block crm-event-info-form-block">
  <div class="event-info">
    <div class="crm-section event_description-section summary">
        {$event.description}
    </div>
    <br>
    <div class="crm-section">
        <div class="label">
          {ts}Is this ceremony open to public? {/ts}
        </div>
        <div class="content">
          {if $event.is_public eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
        </div>
    </div>
    <br>
    <div class="crm-section">
        <div class="label">
          {ts}Do you require a flag?{/ts}
        </div>
        <div class="content">
          {if $event.custom_834 eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
        </div>
    </div>
  </div>

  <div class="clear"></div>
  <div class="crm-section event_date_time-section">
      <div class="label">{ts}When{/ts}</div>
      <div class="content">
            <abbr class="dtstart" title="{$event.event_start_date|crmDate}">
            {$event.event_start_date|crmDate}</abbr>
            {if $event.event_end_date}
                &nbsp; {ts}through{/ts} &nbsp;
                {* Only show end time if end date = start date *}
                {if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate:0:1}">
                    {$event.event_end_date|crmDate:0:1}
                    </abbr>
                {else}
                    <abbr class="dtend" title="{$event.event_end_date|crmDate}">
                    {$event.event_end_date|crmDate}
                    </abbr>
                {/if}
            {/if}
        </div>
    <div class="clear"></div>
  </div>

  {if $location.address.1}
      <div class="crm-section event_address-section">
          <div class="label">{ts}Location{/ts}</div>
          <div class="content">{$location.address.1.display|nl2br}</div>
          <div class="clear"></div>
      </div>
  {/if}

  {if $location.phone.1.phone || $location.email.1.email}
      <div class="crm-section event_contact-section">
          <div class="label">{ts}Contact{/ts}</div>
          <div class="content">
              {* loop on any phones and emails for this event *}
              {foreach from=$location.phone item=phone}
                  {if $phone.phone}
                      {if $phone.phone_type_id}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}:
                          <span class="tel">{$phone.phone} {if $phone.phone_ext}&nbsp;{ts}ext.{/ts} {$phone.phone_ext}{/if} </span> <br />
                      {/if}
              {/foreach}

              {foreach from=$location.email item=email}
                  {if $email.email}
                      {ts}Email:{/ts} <span class="email"><a href="mailto:{$email.email}">{$email.email}</a></span>
                  {/if}
              {/foreach}
          </div>
          <div class="clear"></div>
      </div>
  {/if}

  {foreach from=$creator key=name item=info}
    <div class="crm-section event_$name-section">
        <div class="label">{$info.label}</div>
        <div class="content">{$info.value}</div>
        <div class="clear"></div>
    </div>
  {/foreach}

</div>
