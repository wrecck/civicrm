{* Case Tab Template for Units Ledger *}

<div class="crm-block crm-unitledger-case-tab">
  <div class="crm-submit-buttons">
    <a href="{crmURL p='civicrm/admin/unitledger/import' q="case_id=$caseId"}" class="button">
      <span><i class="crm-i fa-upload"></i> {ts}Import Data{/ts}</span>
    </a>
    <a href="{crmURL p='civicrm/admin/unitledger/recompute' q="case_id=$caseId"}" class="button">
      <span><i class="crm-i fa-refresh"></i> {ts}Recompute Balances{/ts}</span>
    </a>
  </div>

  {* Current Balances Summary *}
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Current Balances{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="content">
          {if $currentBalances}
            <table class="form-layout-compressed">
              <thead>
                <tr>
                  <th>{ts}Program{/ts}</th>
                  <th>{ts}Current Balance{/ts}</th>
                  <th>{ts}Unit Type{/ts}</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$currentBalances key=program item=balance}
                  <tr>
                    <td>{$program}</td>
                    <td class="balance {if $balance >= 0}positive{else}negative{/if}">{$balance|number_format:2}</td>
                    <td>hours</td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          {else}
            <p>{ts}No unit ledger entries found for this case.{/ts}</p>
          {/if}
        </div>
      </div>
    </div>
  </div>

  {* Summary Statistics *}
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Summary Statistics{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="content">
          <table class="form-layout-compressed">
            <tr>
              <td class="label">{ts}Total Entries{/ts}:</td>
              <td>{$summary.total_entries}</td>
            </tr>
            <tr>
              <td class="label">{ts}Total Units{/ts}:</td>
              <td>{$summary.total_units|number_format:2}</td>
            </tr>
            <tr>
              <td class="label">{ts}Date Range{/ts}:</td>
              <td>
                {if $summary.date_range.start}
                  {$summary.date_range.start|date_format:"%Y-%m-%d"} to {$summary.date_range.end|date_format:"%Y-%m-%d"}
                {else}
                  {ts}No entries{/ts}
                {/if}
              </td>
            </tr>
          </table>
          
          {if $summary.programs}
            <h4>{ts}By Program{/ts}</h4>
            <table class="form-layout-compressed">
              <thead>
                <tr>
                  <th>{ts}Program{/ts}</th>
                  <th>{ts}Entries{/ts}</th>
                  <th>{ts}Total Units{/ts}</th>
                  <th>{ts}Current Balance{/ts}</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$summary.programs key=program item=stats}
                  <tr>
                    <td>{$program}</td>
                    <td>{$stats.entries}</td>
                    <td>{$stats.units|number_format:2}</td>
                    <td class="balance {if $stats.balance >= 0}positive{else}negative{/if}">{$stats.balance|number_format:2}</td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          {/if}
        </div>
      </div>
    </div>
  </div>

  {* Ledger Entries *}
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Ledger Entries{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-section">
        <div class="content">
          {if $ledgerEntries}
            <table class="form-layout-compressed">
              <thead>
                <tr>
                  <th>{ts}Date{/ts}</th>
                  <th>{ts}Program{/ts}</th>
                  <th>{ts}Units{/ts}</th>
                  <th>{ts}Type{/ts}</th>
                  <th>{ts}Balance{/ts}</th>
                  <th>{ts}Description{/ts}</th>
                  <th>{ts}Activity{/ts}</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$ledgerEntries item=entry}
                  <tr>
                    <td>{$entry.transaction_date|date_format:"%Y-%m-%d %H:%M"}</td>
                    <td>{$entry.program}</td>
                    <td class="units {if $entry.units >= 0}positive{else}negative{/if}">{$entry.units|number_format:2}</td>
                    <td>{$entry.transaction_type}</td>
                    <td class="balance {if $entry.balance >= 0}positive{else}negative{/if}">{$entry.balance|number_format:2}</td>
                    <td>{$entry.description}</td>
                    <td>
                      {if $entry.activity_id}
                        <a href="{crmURL p='civicrm/activity' q="action=view&id=$entry.activity_id&reset=1"}" target="_blank">
                          {$entry.activity_id}
                        </a>
                      {else}
                        -
                      {/if}
                    </td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          {else}
            <p>{ts}No ledger entries found for this case.{/ts}</p>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<style type="text/css">
.crm-unitledger-case-tab .balance.positive {
  color: #008000;
  font-weight: bold;
}

.crm-unitledger-case-tab .balance.negative {
  color: #800000;
  font-weight: bold;
}

.crm-unitledger-case-tab .units.positive {
  color: #008000;
}

.crm-unitledger-case-tab .units.negative {
  color: #800000;
}

.crm-unitledger-case-tab table {
  width: 100%;
}

.crm-unitledger-case-tab th {
  background-color: #f0f0f0;
  font-weight: bold;
}

.crm-unitledger-case-tab td, .crm-unitledger-case-tab th {
  padding: 8px;
  border: 1px solid #ddd;
  text-align: left;
}

.crm-unitledger-case-tab tr:nth-child(even) {
  background-color: #f9f9f9;
}
</style>

<script type="text/javascript">
{literal}
CRM.$(function($) {
  // Initialize accordions
  $('.crm-accordion-wrapper').crmAccordions();
  
  // Add export functionality
  $('.crm-submit-buttons').append(
    '<a href="#" class="button export-ledger" style="margin-left: 10px;">' +
    '<span><i class="crm-i fa-download"></i> Export CSV</span>' +
    '</a>'
  );
  
  $('.export-ledger').on('click', function(e) {
    e.preventDefault();
    
    // Create CSV content
    var csvContent = "Date,Program,Units,Type,Balance,Description,Activity\n";
    
    $('.crm-unitledger-case-tab table tbody tr').each(function() {
      var row = [];
      $(this).find('td').each(function() {
        var text = $(this).text().trim();
        // Handle activity links
        if ($(this).find('a').length > 0) {
          text = $(this).find('a').text().trim();
        }
        row.push('"' + text.replace(/"/g, '""') + '"');
      });
      csvContent += row.join(',') + '\n';
    });
    
    // Download CSV
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'unit_ledger_case_{/literal}{$caseId}{literal}.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
});
{/literal}
</script>
