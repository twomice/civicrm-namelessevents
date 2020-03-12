{* HEADER *}

<div class="help">{ts}For online registration, each profile may be shwon or hidden based on the auto-calculated contact type.{/ts}</div>

<div class="crm-block crm-form-block crm-event-student-progress-form-block">
  <div id="student_progress">
    <table class="form-layout-compressed">
      <tr class="crm-event-student-progress-form-block-is_student_progress">
        <td class="label">{$form.is_student_progress.label}</td>
        <td>{$form.is_student_progress.html}
          <span class="description">Enable Student Progress handling for this event?</span>
        </td>
      </tr>
      <tr class="crm-event-student-progress-form-block-profile_subtypes">
        <td></td>
        <td style="padding-top: 2em">
          <table>
            <tr>
            <th rowspan="2" style="padding: 1em 1em 0">{ts}Profile{/ts}</th>
            <th colspan="{$subTypes|@count}" style="padding: 1em 1em 0">{ts}Display for contact sub-types{/ts}</th>
            </tr>
            <tr>
              {foreach from=$subTypes key=subTypeId item=subTypeLabel}
                <th style="text-align: center">{$subTypeLabel}</th>
              {/foreach}
            </tr>
            <tbody>
            {foreach from=$profiles key=profileId item=profileTitle}
              <tr class="{cycle values="odd-row,even-row"}">
                <td>{$profileTitle}</td>
                {foreach from=$subTypes key=subTypeId item=subTypeLabel}
                  <td align="center">{$form.profile.$profileId.$subTypeId.html}</td>
                {/foreach}
              </tr>
            {/foreach}
            </tbody>
          </table>
        </td>
      </tr>
    </table>
  </div>
</div>
            
{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
