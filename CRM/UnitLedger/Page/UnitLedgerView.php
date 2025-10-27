<?php

use CRM_UnitLedger_ExtensionUtil as E;

class CRM_UnitLedger_Page_UnitLedgerView extends CRM_Core_Page {

	public function run() {
		try {
			// Optional filters
			$caseId = CRM_Utils_Request::retrieve('caseid', 'Positive');
			$contactId = CRM_Utils_Request::retrieve('cid', 'Positive');

			CRM_Utils_System::setTitle(E::ts('Unit Ledger - Cases'));

			$caseData = $this->getCaseData($caseId, $contactId);

			// Assign data to Smarty template
			$this->assign('caseData', $caseData);
			$this->assign('caseId', $caseId);
			$this->assign('contactId', $contactId);
			
			// Use CiviCRM's standard page rendering
			parent::run();
			return;
		} catch (Exception $e) {
			$this->assign('error', $e->getMessage());
			parent::run();
		}
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
				huu.total_housing_units_remaining_313 AS total_housing_units_remaining,
				euu.total_employment_units_allocated_314 AS total_employment_units_allocated,
				euu.total_employment_units_delivered_315 AS total_employment_units_delivered,
				euu.total_employment_units_remaining_316 AS total_employment_units_remaining
			FROM civicrm_case cs
			LEFT JOIN civicrm_case_type ct ON ct.id = cs.case_type_id
			LEFT JOIN civicrm_option_value ov ON ov.value = cs.status_id AND ov.option_group_id = 26
			LEFT JOIN  civicrm_case_contact ccc ON ccc.case_id = cs.id
			LEFT JOIN  civicrm_contact c ON c.id = ccc.contact_id
			LEFT JOIN  civicrm_value_housing_units_41 huu ON huu.entity_id = cs.id
			LEFT JOIN  civicrm_value_employment_un_42 euu ON euu.entity_id = cs.id
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

		try {
			$dao = CRM_Core_DAO::executeQuery($sql, $params);
		} catch (Exception $e) {
			return [];
		}
		
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
				'total_employment_units_allocated' => $dao->total_employment_units_allocated,
				'total_employment_units_delivered' => $dao->total_employment_units_delivered,
				'total_employment_units_remaining' => $dao->total_employment_units_remaining,
			];
		}
		return $rows;
	}
}


