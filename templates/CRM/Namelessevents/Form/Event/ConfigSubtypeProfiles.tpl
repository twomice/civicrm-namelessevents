{* HEADER *}

<div class="help">{ts}For online registration, each profile may be hidden based on the auto-calculated contact type.{/ts}</div>
<div class="crm-block crm-form-block crm-event-manage-location-form-block">

  <table>
    <thead>
    <th>{ts}This profile ...{/ts}</th>
    <th>{ts}... will only be displayed for this contact sub-type{/ts}</th>
    </thead>
    <tbody>
    {foreach from=$contactSubTypes item=contactSubType}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$form[$contactSubType].html}</td>
        <td>{$form[$contactSubType].label}</td>
      </tr>
    {/foreach}
    </tbody>
  </table>


  {* FOOTER *}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

 </div>