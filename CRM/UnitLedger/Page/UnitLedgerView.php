<?php

use CRM_UnitLedger_ExtensionUtil as E;

class CRM_UnitLedger_Page_UnitLedgerView extends CRM_Core_Page {

	public function run() {
		// Optional filters
		$caseId = CRM_Utils_Request::retrieve('caseid', 'Positive');
		$contactId = CRM_Utils_Request::retrieve('cid', 'Positive');

		CRM_Utils_System::setTitle(E::ts('Unit Ledger - Cases'));

		$caseData = $this->getCaseData($caseId, $contactId);

		// Generate HTML directly instead of using Smarty template
		$html = $this->generateHTML($caseData, $caseId, $contactId);
		
		// Output the HTML
		echo $html;
		return;
	}

	/**
	 * Generate HTML for the case view
	 */
	private function generateHTML($caseData, $caseId, $contactId) {
		$html = '<div class="crm-container">';
		$html .= '<div class="crm-section">';
		$html .= '<h2>' . E::ts('Unit Ledger - Cases') . '</h2>';
		
		// Filter summary
		$filters = [];
		if ($caseId) $filters[] = 'Case: ' . $caseId;
		if ($contactId) $filters[] = 'Contact: ' . $contactId;
		
		if (!empty($filters)) {
			$html .= '<div class="messages status">' . implode(' | ', $filters) . '</div>';
		}
		
		if (!empty($caseData)) {
			$html .= '<table class="selector">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th>' . E::ts('Case ID') . '</th>';
			$html .= '<th>' . E::ts('Contact') . '</th>';
			$html .= '<th>' . E::ts('Case Type') . '</th>';
			$html .= '<th>' . E::ts('Status') . '</th>';
			$html .= '<th>' . E::ts('Allocated') . '</th>';
			$html .= '<th>' . E::ts('Delivered') . '</th>';
			$html .= '<th>' . E::ts('Remaining') . '</th>';
			$html .= '<th>' . E::ts('Created Date') . '</th>';
			$html .= '<th>' . E::ts('Modified Date') . '</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			
			$i = 0;
			foreach ($caseData as $row) {
				$class = (++$i % 2 == 0) ? 'even-row' : 'odd-row';
				$html .= '<tr class="' . $class . '">';
				$html .= '<td>' . $row['id'] . '</td>';
				$html .= '<td>' . $row['display_name'] . '</td>';
				$html .= '<td>' . ($row['case_type_title'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['case_status_label'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['total_housing_units_allocated'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['total_housing_units_delivered'] ?: '-') . '</td>';
				$html .= '<td>' . ($row['total_housing_units_remaining'] ?: '-') . '</td>';
				$html .= '<td>' . CRM_Utils_Date::customFormat($row['created_date']) . '</td>';
				$html .= '<td>' . CRM_Utils_Date::customFormat($row['modified_date']) . '</td>';
				$html .= '</tr>';
			}
			
			$html .= '</tbody>';
			$html .= '</table>';
		} else {
			$html .= '<div class="status">';
			$html .= '<p>' . E::ts('No cases found.') . '</p>';
			$html .= '</div>';
		}
		
		$html .= '</div>';
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Fetch case data with optional filters
	 */
	private function getCaseData($caseId = NULL, $contactId = NULL) {
		$sql = "
			SELECT 
				cs.id,
				cs.case_type_id,
				cs.subject,
				cs.created_date,
				cs.modified_date,
				cs.status_id,
				ct.title AS case_type_title,
				ov.label AS case_status_label,
				c.display_name AS display_name,
				huu.total_housing_units_allocated_311 AS total_housing_units_allocated,
				huu.total_housing_units_delivered_312 AS total_housing_units_delivered,
				huu.total_housing_units_remaining_313 AS total_housing_units_remaining
			FROM civicrm_case cs
			LEFT JOIN civicrm_case_type ct ON ct.id = cs.case_type_id
			LEFT JOIN civicrm_option_value ov ON ov.value = cs.status_id AND ov.option_group_id = 26
			LEFT JOIN  civicrm_case_contact ccc ON ccc.case_id = cs.id
			LEFT JOIN  civicrm_contact c ON c.id = ccc.contact_id
			LEFT JOIN  civicrm_value_housing_units_41 huu ON huu.entity_id = cs.id
			WHERE cs.is_deleted = 0
		";

		$params = [];
		$idx = 1;
		if ($caseId) {
			$sql .= " AND cs.id = %$idx";
			$params[$idx++] = [$caseId, 'Integer'];
		}
		if ($contactId) {
			$sql .= " AND cs.id IN (
				SELECT case_id FROM civicrm_case_contact WHERE contact_id = %$idx
			)";
			$params[$idx++] = [$contactId, 'Integer'];
		}

		$sql .= " ORDER BY cs.created_date DESC";

		$dao = CRM_Core_DAO::executeQuery($sql, $params);
		$rows = [];
		while ($dao->fetch()) {
			$rows[] = [
				'id' => (int) $dao->id,
				'case_type_id' => $dao->case_type_id,
				'subject' => $dao->subject,
				'created_date' => $dao->created_date,
				'modified_date' => $dao->modified_date,
				'status_id' => $dao->status_id,
				'case_type_title' => $dao->case_type_title,
				'case_status_label' => $dao->case_status_label,
				'display_name' => $dao->display_name,
				'total_housing_units_allocated' => $dao->total_housing_units_allocated,
				'total_housing_units_delivered' => $dao->total_housing_units_delivered,
				'total_housing_units_remaining' => $dao->total_housing_units_remaining,
			];
		}
		return $rows;
	}
}


