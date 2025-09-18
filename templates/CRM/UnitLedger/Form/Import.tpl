{* Unit Ledger Import Form Template *}

<div class="crm-block crm-form-block crm-unitledger-import-form-block">
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Import Options{/ts}
    </div>
    <div class="crm-accordion-body">
      <form method="post" action="{crmURL p='civicrm/admin/unitledger/import' q='action=import'}" enctype="multipart/form-data">
        <div class="crm-section">
          <div class="label">{$form.uploadFile.label}</div>
          <div class="content">
            {$form.uploadFile.html}
            <div class="description">
              {ts}Upload a CSV file with unit ledger data. Maximum file size: 10MB{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.dry_run.label}</div>
          <div class="content">
            {$form.dry_run.html}
            <div class="description">
              {ts}Preview the import without actually creating records{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.skip_duplicates.label}</div>
          <div class="content">
            {$form.skip_duplicates.html}
            <div class="description">
              {ts}Skip records that already exist in the ledger{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.create_activities.label}</div>
          <div class="content">
            {$form.create_activities.html}
            <div class="description">
              {ts}Create FCS Authorization activities for imported records{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.case_id.label}</div>
          <div class="content">
            {$form.case_id.html}
            <div class="description">
              {ts}Optional: Associate all imported records with a specific case{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.start_date.label}</div>
          <div class="content">
            {$form.start_date.html}
            <div class="description">
              {ts}Optional: Only import records from this date onwards{/ts}
            </div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.end_date.label}</div>
          <div class="content">
            {$form.end_date.html}
            <div class="description">
              {ts}Optional: Only import records up to this date{/ts}
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
      {ts}CSV Format Requirements{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="content">
          <h4>{ts}Required Fields{/ts}</h4>
          <ul>
            <li><strong>program</strong> - Program name (e.g., FCS, WellPoint)</li>
            <li><strong>units</strong> - Number of units (numeric)</li>
            <li><strong>transaction_date</strong> - Date of transaction (YYYY-MM-DD HH:MM:SS)</li>
          </ul>
          
          <h4>{ts}Optional Fields{/ts}</h4>
          <ul>
            <li><strong>unit_type</strong> - Type of units (default: hours)</li>
            <li><strong>transaction_type</strong> - Type of transaction (default: import)</li>
            <li><strong>description</strong> - Description of the transaction</li>
            <li><strong>contact_id</strong> - Contact ID</li>
            <li><strong>case_id</strong> - Case ID</li>
          </ul>
          
          <h4>{ts}Example CSV{/ts}</h4>
          <pre>program,units,transaction_date,unit_type,description,contact_id,case_id
FCS,2.5,2025-01-27 10:00:00,hours,FCS Authorization,123,456
WellPoint,1.0,2025-01-27 11:00:00,hours,WellPoint Authorization,123,456</pre>
        </div>
      </div>
    </div>
  </div>

  {* Show results if available *}
  {if $previewResults || $importResults}
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
      <div class="crm-accordion-header">
        <div class="icon crm-accordion-pointer"></div>
        {if $isDryRun}
          {ts}Preview Results{/ts}
        {else}
          {ts}Import Results{/ts}
        {/if}
      </div>
      <div class="crm-accordion-body">
        <div class="crm-section">
          <div class="content">
            {assign var="results" value=$previewResults|default:$importResults}
            
            <div class="crm-summary-row">
              <div class="crm-summary-label">{ts}Total Records{/ts}:</div>
              <div class="crm-summary-value">{$results.total}</div>
            </div>
            
            {if !$isDryRun}
              <div class="crm-summary-row">
                <div class="crm-summary-label">{ts}Created{/ts}:</div>
                <div class="crm-summary-value">{$results.created}</div>
              </div>
              
              <div class="crm-summary-row">
                <div class="crm-summary-label">{ts}Updated{/ts}:</div>
                <div class="crm-summary-value">{$results.updated}</div>
              </div>
            {/if}
            
            <div class="crm-summary-row">
              <div class="crm-summary-label">{ts}Skipped{/ts}:</div>
              <div class="crm-summary-value">{$results.skipped}</div>
            </div>
            
            {if $results.errors}
              <div class="crm-summary-row">
                <div class="crm-summary-label">{ts}Errors{/ts}:</div>
                <div class="crm-summary-value" style="color: red;">{count($results.errors)}</div>
              </div>
            {/if}
            
            {if $results.records}
              <h4>{ts}Record Details{/ts}</h4>
              <table class="form-layout-compressed">
                <thead>
                  <tr>
                    <th>{ts}Row{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th>{ts}Program{/ts}</th>
                    <th>{ts}Units{/ts}</th>
                    <th>{ts}Date{/ts}</th>
                    <th>{ts}Reason/ID{/ts}</th>
                  </tr>
                </thead>
                <tbody>
                  {foreach from=$results.records item=record}
                    <tr>
                      <td>{$record.row}</td>
                      <td class="status-{$record.status}">{$record.status}</td>
                      <td>{$record.data.program}</td>
                      <td>{$record.data.units}</td>
                      <td>{$record.data.transaction_date}</td>
                      <td>
                        {if $record.ledger_id}
                          <a href="{crmURL p='civicrm/admin/unitledger/view' q="id=$record.ledger_id"}" target="_blank">
                            {$record.ledger_id}
                          </a>
                        {elseif $record.reason}
                          {$record.reason}
                        {else}
                          -
                        {/if}
                      </td>
                    </tr>
                  {/foreach}
                </tbody>
              </table>
            {/if}
            
            {if $results.errors}
              <h4>{ts}Errors{/ts}</h4>
              <table class="form-layout-compressed">
                <thead>
                  <tr>
                    <th>{ts}Row{/ts}</th>
                    <th>{ts}Error{/ts}</th>
                  </tr>
                </thead>
                <tbody>
                  {foreach from=$results.errors item=error}
                    <tr>
                      <td>{$error.row}</td>
                      <td style="color: red;">{$error.error}</td>
                    </tr>
                  {/foreach}
                </tbody>
              </table>
            {/if}
          </div>
        </div>
      </div>
    </div>
  {/if}
</div>

<style type="text/css">
.crm-unitledger-import-form-block .status-created {
  color: #008000;
  font-weight: bold;
}

.crm-unitledger-import-form-block .status-valid {
  color: #008000;
}

.crm-unitledger-import-form-block .status-skipped {
  color: #ff8000;
}

.crm-unitledger-import-form-block .status-error {
  color: #800000;
}

.crm-unitledger-import-form-block .crm-summary-row {
  display: flex;
  margin-bottom: 10px;
}

.crm-unitledger-import-form-block .crm-summary-label {
  font-weight: bold;
  width: 150px;
}

.crm-unitledger-import-form-block .crm-summary-value {
  flex: 1;
}

.crm-unitledger-import-form-block table {
  width: 100%;
  margin-top: 20px;
}

.crm-unitledger-import-form-block th {
  background-color: #f0f0f0;
  font-weight: bold;
}

.crm-unitledger-import-form-block td, .crm-unitledger-import-form-block th {
  padding: 8px;
  border: 1px solid #ddd;
  text-align: left;
}

.crm-unitledger-import-form-block tr:nth-child(even) {
  background-color: #f9f9f9;
}
</style>

<script type="text/javascript">
{literal}
CRM.$(function($) {
  // Initialize accordions
  $('.crm-accordion-wrapper').crmAccordions();
  
  // Add file validation
  $('input[type="file"]').on('change', function() {
    var file = this.files[0];
    if (file) {
      var size = file.size / 1024 / 1024; // Size in MB
      if (size > 10) {
        alert('File size exceeds 10MB limit');
        this.value = '';
      }
    }
  });
  
  // Add date validation
  $('input[name="start_date"], input[name="end_date"]').on('change', function() {
    var startDate = $('input[name="start_date"]').val();
    var endDate = $('input[name="end_date"]').val();
    
    if (startDate && endDate) {
      if (new Date(startDate) > new Date(endDate)) {
        alert('End date must be after start date');
        $('input[name="end_date"]').val('');
      }
    }
  });
});
{/literal}
</script>
