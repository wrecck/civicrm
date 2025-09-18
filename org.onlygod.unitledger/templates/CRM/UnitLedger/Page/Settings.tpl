{* Unit Ledger Settings Template *}

<div class="crm-block crm-form-block crm-unitledger-settings-form-block">
  <div class="crm-submit-buttons">
    <a href="{crmURL p='civicrm/admin/unitledger/settings' q='action=add'}" class="button">
      <span><i class="crm-i fa-plus"></i> {ts}Add New Mapping{/ts}</span>
    </a>
  </div>

  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Current Program Mappings{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="label">{$form.program_mappings_title.label}</div>
        <div class="content">
          <table class="form-layout-compressed">
            <thead>
              <tr>
                <th>{ts}Activity Type ID{/ts}</th>
                <th>{ts}Activity Type Name{/ts}</th>
                <th>{ts}Program{/ts}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$programMappings key=activityTypeId item=program}
                <tr>
                  <td>{$activityTypeId}</td>
                  <td>{$activityTypes.$activityTypeId|default:"Unknown"}</td>
                  <td>{$program}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Current Unit Multipliers{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="label">{$form.unit_multipliers_title.label}</div>
        <div class="content">
          <table class="form-layout-compressed">
            <thead>
              <tr>
                <th>{ts}Program{/ts}</th>
                <th>{ts}Multiplier{/ts}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$unitMultipliers key=program item=multiplier}
                <tr>
                  <td>{$program}</td>
                  <td>{$multiplier}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Edit Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <form method="post" action="{crmURL p='civicrm/admin/unitledger/settings' q='action=update'}">
        <div class="crm-section">
          <div class="label">{$form.program_mappings.label}</div>
          <div class="content">
            {$form.program_mappings.html}
            <div class="description">
              {ts}Map activity type IDs to program names. Format: {"activity_type_id": "program_name"}{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.unit_multipliers.label}</div>
          <div class="content">
            {$form.unit_multipliers.html}
            <div class="description">
              {ts}Set unit multipliers for each program. Format: {"program_name": multiplier_value}{/ts}
            </div>
          </div>
        </div>

        <div class="crm-submit-buttons">
          {$form.buttons.html}
        </div>
      </form>
    </div>
  </div>

  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Available Activity Types{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="content">
          <table class="form-layout-compressed">
            <thead>
              <tr>
                <th>{ts}ID{/ts}</th>
                <th>{ts}Name{/ts}</th>
                <th>{ts}Description{/ts}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$activityTypes key=id item=name}
                <tr>
                  <td>{$id}</td>
                  <td>{$name}</td>
                  <td>{ts}Use this ID in your program mappings{/ts}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
{literal}
CRM.$(function($) {
  // Initialize accordions
  $('.crm-accordion-wrapper').crmAccordions();
  
  // Add form validation
  $('form').on('submit', function(e) {
    var programMappings = $('#program_mappings').val();
    var unitMultipliers = $('#unit_multipliers').val();
    
    try {
      JSON.parse(programMappings);
    } catch (e) {
      alert('Invalid JSON format for program mappings: ' + e.message);
      e.preventDefault();
      return false;
    }
    
    try {
      JSON.parse(unitMultipliers);
    } catch (e) {
      alert('Invalid JSON format for unit multipliers: ' + e.message);
      e.preventDefault();
      return false;
    }
  });
});
{/literal}
</script>
