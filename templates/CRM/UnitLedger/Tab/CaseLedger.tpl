{* Case Ledger Tab - renders a simple grid of ledger entries *}
<div class="crm-container">
	<div class="crm-section">
		<h3>{ts}Unit Ledger{/ts} {if $caseId} - {ts}Case#{/ts}{$caseId}{/if}</h3>

		{if $ledgerData && count($ledgerData)}
			<table class="selector">
				<thead>
					<tr>
						<th>{ts}Date{/ts}</th>
						<th>{ts}Activity{/ts}</th>
						<th>{ts}Contact{/ts}</th>
						<th>{ts}Program{/ts}</th>
						<th>{ts}Entry Type{/ts}</th>
						<th>{ts}Units Δ{/ts}</th>
						<th>{ts}Balance After{/ts}</th>
						<th>{ts}Operation{/ts}</th>
						<th>{ts}Description{/ts}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$ledgerData item=row}
						<tr class="{cycle values='odd-row,even-row'}">
							<td>{$row.created_date|crmDate}</td>
							<td>{if $row.activity_subject}{$row.activity_subject}{else}-{/if}</td>
							<td>{if $row.contact_name}{$row.contact_name}{else}{$row.contact_id}{/if}</td>
							<td>{$row.program}</td>
							<td>{$row.entry_type}</td>
							<td class="{if $row.units_delta > 0}status-ok{elseif $row.units_delta < 0}status-warning{/if}">{if $row.units_delta > 0}+{/if}{$row.units_delta}</td>
							<td>{$row.balance_after}</td>
							<td>{$row.operation}</td>
							<td>{$row.description}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="status">
				{ts}No ledger entries found for this case.{/ts}
			</div>
		{/if}
	</div>
</div>

