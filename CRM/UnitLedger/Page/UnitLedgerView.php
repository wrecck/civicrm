<?php

use CRM_UnitLedger_ExtensionUtil as E;

class CRM_UnitLedger_Page_UnitLedgerView extends CRM_Core_Page {

	public function run() {
		// Optional filters
		$caseId = CRM_Utils_Request::retrieve('caseid', 'Positive');
		$contactId = CRM_Utils_Request::retrieve('cid', 'Positive');
		$program = CRM_Utils_Request::retrieve('program', 'String');
		$entryType = CRM_Utils_Request::retrieve('entry_type', 'String');

		CRM_Utils_System::setTitle(E::ts('Unit Ledger'));

		$ledgerData = $this->getLedgerData($caseId, $contactId, $program, $entryType);

		// Generate HTML directly instead of using Smarty template
		$html = $this->generateHTML($ledgerData, $caseId, $contactId, $program, $entryType);
		
		// Output the HTML
		echo $html;
		return;
	}

	/**
	 * Generate HTML for the ledger view
	 */
	private function generateHTML($ledgerData, $caseId, $contactId, $program, $entryType) {
		$html = '<div class="crm-container">';
		$html .= '<div class="crm-section">';
		$html .= '<h2>' . E::ts('Unit Ledger') . '</h2>';
		
		// Filter summary
		$filters = [];
		if ($caseId) $filters[] = 'Case: ' . $caseId;
		if ($contactId) $filters[] = 'Contact: ' . $contactId;
		if ($program) $filters[] = 'Program: ' . $program;
		if ($entryType) $filters[] = 'Entry Type: ' . $entryType;
		
		if (!empty($filters)) {
			$html .= '<div class="messages status">' . implode(' | ', $filters) . '</div>';
		}
		
		if (!empty($ledgerData)) {
			$html .= '<table class="selector">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th>' . E::ts('Date') . '</th>';
			$html .= '<th>' . E::ts('Activity') . '</th>';
			$html .= '<th>' . E::ts('Activity Type') . '</th>';
			$html .= '<th>' . E::ts('Contact') . '</th>';
			$html .= '<th>' . E::ts('Program') . '</th>';
			$html .= '<th>' . E::ts('Entry Type') . '</th>';
			$html .= '<th>' . E::ts('Units Δ') . '</th>';
			$html .= '<th>' . E::ts('Balance After') . '</th>';
			$html .= '<th>' . E::ts('Operation') . '</th>';
			$html .= '<th>' . E::ts('Description') . '</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			
			foreach ($ledgerData as $row) {
				$class = (++$i % 2 == 0) ? 'even-row' : 'odd-row';
				$html .= '<tr class="' . $class . '">';
				$html .= '<td>' . CRM_Utils_Date::customFormat($row['created_date']) . '</td>';
				$html .= '<td>' . ($row['activity_subject'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['activity_type_name'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['contact_name'] ?: $row['contact_id']) . '</td>';
				$html .= '<td>' . $row['program'] . '</td>';
				$html .= '<td>' . $row['entry_type'] . '</td>';
				
				$deltaClass = '';
				if ($row['units_delta'] > 0) $deltaClass = 'status-ok';
				elseif ($row['units_delta'] < 0) $deltaClass = 'status-warning';
				
				$html .= '<td class="' . $deltaClass . '">';
				if ($row['units_delta'] > 0) $html .= '+';
				$html .= $row['units_delta'] . '</td>';
				$html .= '<td>' . $row['balance_after'] . '</td>';
				$html .= '<td>' . $row['operation'] . '</td>';
				$html .= '<td>' . ($row['description'] ?: '') . '</td>';
				$html .= '</tr>';
			}
			
			$html .= '</tbody>';
			$html .= '</table>';
		} else {
			$html .= '<div class="status">';
			$html .= '<p>' . E::ts('No ledger entries found.') . '</p>';
			$html .= '</div>';
		}
		
		$html .= '</div>';
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Fetch ledger data with optional filters
	 */
	private function getLedgerData($caseId = NULL, $contactId = NULL, $program = NULL, $entryType = NULL) {
		$sql = "
			SELECT 
				ul.id,
				ul.activity_id,
				ul.case_id,
				ul.contact_id,
				ul.program,
				ul.entry_type,
				ul.units_delta,
				ul.balance_after,
				ul.operation,
				ul.description,
				ul.created_date,
				ul.created_by,
				a.subject AS activity_subject,
				ov.label AS activity_type_name,
				c.display_name AS contact_name
			FROM civicrm_unit_ledger ul
			LEFT JOIN civicrm_activity a ON a.id = ul.activity_id
			LEFT JOIN civicrm_option_value ov 
				ON ov.value = a.activity_type_id 
				AND ov.option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_type' LIMIT 1)
			LEFT JOIN civicrm_contact c ON c.id = ul.contact_id
			WHERE 1=1
		";

		$params = [];
		$idx = 1;
		if ($caseId) {
			$sql .= " AND ul.case_id = %$idx";
			$params[$idx++] = [$caseId, 'Integer'];
		}
		if ($contactId) {
			$sql .= " AND ul.contact_id = %$idx";
			$params[$idx++] = [$contactId, 'Integer'];
		}
		if ($program) {
			$sql .= " AND ul.program = %$idx";
			$params[$idx++] = [$program, 'String'];
		}
		if ($entryType) {
			$sql .= " AND ul.entry_type = %$idx";
			$params[$idx++] = [$entryType, 'String'];
		}

		$sql .= " ORDER BY ul.created_date DESC, ul.id DESC";

		$dao = CRM_Core_DAO::executeQuery($sql, $params);
		$rows = [];
		while ($dao->fetch()) {
			$rows[] = [
				'id' => (int) $dao->id,
				'activity_id' => $dao->activity_id,
				'activity_subject' => $dao->activity_subject,
				'activity_type_name' => $dao->activity_type_name,
				'contact_id' => $dao->contact_id,
				'contact_name' => $dao->contact_name,
				'program' => $dao->program,
				'entry_type' => $dao->entry_type,
				'units_delta' => (int) $dao->units_delta,
				'balance_after' => (int) $dao->balance_after,
				'operation' => $dao->operation,
				'description' => $dao->description,
				'created_date' => $dao->created_date,
				'created_by' => $dao->created_by,
			];
		}
		return $rows;
	}
}


