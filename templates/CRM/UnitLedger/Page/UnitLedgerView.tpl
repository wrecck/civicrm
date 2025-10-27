{* Unit Ledger Page - displays case information with unit allocations *}
<div class="crm-container">
	<div class="crm-section">
		<h3>{ts}Unit Ledger - Cases{/ts} {if $caseId} - {ts}Case#{/ts}{$caseId}{/if}</h3>

		{* Filter summary *}
		{assign var="filters" value=""}
		{if $caseId}{assign var="filters" value=$filters|cat:"Case: "|cat:$caseId}{/if}
		{if $contactId}
			{if $filters}{assign var="filters" value=$filters|cat:" | "}{/if}
			{assign var="filters" value=$filters|cat:"Contact: "|cat:$contactId}
		{/if}

		{if $filters}
			<div class="messages status">{$filters}</div>
		{/if}

		{if $error}
			<div class="crm-container">
				<div class="crm-section">
					<h2>Unit Ledger - Error</h2>
					<p>Error: {$error}</p>
				</div>
			</div>
		{elseif $caseData && count($caseData)}
			<table class="selector">
				<thead>
					<tr>
						<th>{ts}Case ID{/ts}</th>
						<th>{ts}Contact{/ts}</th>
						<th>{ts}Case Type{/ts}</th>
						<th>{ts}Status{/ts}</th>
						<th>{ts}Allocated{/ts}</th>
						<th>{ts}Delivered{/ts}</th>
						<th>{ts}Remaining{/ts}</th>
						<th>{ts}Created Date{/ts}</th>
						<th>{ts}Modified Date{/ts}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$caseData item=row name=caseLoop}
						{* Logic to determine allocated/delivered/remaining based on case type *}
						{assign var="total_units_allocated" value="-"}
						{assign var="total_units_delivered" value="-"}
						{assign var="total_units_remaining" value="-"}
						
						{if $row.case_type_title == 'FCS Housing'}
							{assign var="total_units_allocated" value=$row.total_housing_units_allocated}
							{assign var="total_units_delivered" value=$row.total_housing_units_delivered}
							{assign var="total_units_remaining" value=$row.total_housing_units_remaining}
						{elseif $row.case_type_title == 'FCS Employment'}
							{assign var="total_units_allocated" value=$row.total_employment_units_allocated}
							{assign var="total_units_delivered" value=$row.total_employment_units_delivered}
							{assign var="total_units_remaining" value=$row.total_employment_units_remaining}
						{/if}

						<tr class="{if $smarty.foreach.caseLoop.iteration % 2 == 0}even-row{else}odd-row{/if}">
							<td>{$row.id}</td>
							<td>{$row.display_name}</td>
							<td>{$row.case_type_title|default:"-"}</td>
							<td>{$row.case_status_label|default:"-"}</td>
							<td>{$total_units_allocated|default:"-"}</td>
							<td>{$total_units_delivered|default:"-"}</td>
							<td>{$total_units_remaining|default:"-"}</td>
							<td>{$row.created_date|crmDate}</td>
							<td>{$row.modified_date|crmDate}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="status">
				<p>{ts}No cases found.{/ts}</p>
			</div>
		{/if}
	</div>
</div>

