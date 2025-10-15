<?php

require_once 'unitledger.civix.php';

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Implementation of hook_civicrm_config
 */
function unitledger_civicrm_config(&$config) {
  _unitledger_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 */
function unitledger_civicrm_install() {
  _unitledger_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 */
function unitledger_civicrm_enable() {
  _unitledger_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_tabset().
 * Adds a "Unit Ledger" tab on the Case view which renders a grid from civicrm_unit_ledger.
 */
function unitledger_civicrm_tabset($tabsetName, &$tabs, $context) {
	// We only add the tab on the Case view tabset
	if ($tabsetName !== 'civicrm/case/view') {
		return;
	}

	$caseId = isset($context['case_id']) ? (int) $context['case_id'] : 0;
	$contactId = isset($context['contact_id']) ? (int) $context['contact_id'] : 0;

	// Fetch ledger data for this case (limited for performance; template can show all)
	$ledgerData = unitledger_get_case_ledger_data($caseId);

	// Render content using a Smarty template
	$tpl = CRM_Core_Smarty::singleton();
	$tpl->assign('ledgerData', $ledgerData);
	$tpl->assign('caseId', $caseId);
	$tpl->assign('contactId', $contactId);
	$content = $tpl->fetch('CRM/UnitLedger/Tab/CaseLedger2.tpl');

	$tabs[] = [
		'id' => 'unitledger_case_tab',
		'url' => NULL,
		'title' => E::ts('Unit Ledger'),
		'weight' => 95,
		'count' => count($ledgerData),
		'content' => $content,
		'permission' => 'access CiviCRM',
	];
}

/**
 * Helper: Retrieve ledger entries for a given case.
 *
 * @param int $caseId
 * @return array<int,array<string,mixed>>
 */
function unitledger_get_case_ledger_data($caseId) {
	if (empty($caseId)) {
		return [];
	}

	$sql = "
		SELECT 
			cs.id,
			cs.case_type_id,
			cs.subject,
			cs.created_date,
			cs.modified_date,
			cs.status_id,
			ct.title AS case_type_title,
			ov.label AS case_status_label
		FROM civicrm_case cs
		LEFT JOIN civicrm_case_type ct ON ct.id = cs.case_type_id
		LEFT JOIN civicrm_option_value ov 
			ON ov.value = cs.status_id 
			AND ov.option_group_id = 26
		WHERE cs.id = %1
		ORDER BY cs.created_date DESC
	";

	$params = [
		1 => [$caseId, 'Integer'],
	];

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
		];
	}

	return $rows;
}

/**
 * Implements hook_civicrm_alterMenu().
 * Registers a route for the standalone Unit Ledger page.
 */
function unitledger_civicrm_alterMenu(&$items) {
	$items['civicrm/unitledger'] = [
		'page_callback' => 'CRM_UnitLedger_Page_UnitLedgerView',
		'access_arguments' => ['access CiviCRM'],
		'is_public' => 0,
	];
}

/**
 * Implements hook_civicrm_navigationMenu().
 * Adds Unit Ledger to the main navigation menu - simplified version.
 */
function unitledger_civicrm_navigationMenu(&$menu) {
	// Simple approach - just add to Reports section
	$menu['Reports']['child']['unit_ledger'] = [
		'label' => E::ts('Unit Ledger'),
		'name' => 'unit_ledger',
		'url' => CRM_Utils_System::url('civicrm/unitledger', 'reset=1', true),
		'permission' => 'access CiviCRM',
		'operator' => 'OR',
		'separator' => 0,
		'active' => 1,
	];
}


