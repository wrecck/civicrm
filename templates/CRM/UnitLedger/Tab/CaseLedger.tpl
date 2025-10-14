{* Case Information Tab - displays case details *}
<div class="crm-container">
	<div class="crm-section">
		<h3>{ts}Case Information{/ts} {if $caseId} - {ts}Case#{/ts}{$caseId}{/if}</h3>

		{if $ledgerData && count($ledgerData)}
			<table class="selector">
				<thead>
					<tr>
						<th>{ts}Case ID{/ts}</th>
						<th>{ts}Subject{/ts}</th>
						<th>{ts}Case Type{/ts}</th>
						<th>{ts}Status{/ts}</th>
						<th>{ts}Created Date{/ts}</th>
						<th>{ts}Modified Date{/ts}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$ledgerData item=row}
						<tr class="{cycle values='odd-row,even-row'}">
							<td>{$row.id}</td>
							<td>{$row.subject}</td>
							<td>{$row.case_type_title}</td>
							<td>{$row.case_status_label}</td>
							<td>{$row.created_date|crmDate}</td>
							<td>{$row.modified_date|crmDate}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="status">
				{ts}No case information found.{/ts}
			</div>
		{/if}
	</div>
</div>

