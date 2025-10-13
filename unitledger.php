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
	$content = $tpl->fetch('CRM/UnitLedger/Tab/CaseLedger.tpl');

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
			a.activity_type_id,
			ov.label AS activity_type_name,
			c.display_name AS contact_name
		FROM civicrm_unit_ledger ul
		LEFT JOIN civicrm_activity a ON a.id = ul.activity_id
		LEFT JOIN civicrm_option_value ov 
			ON ov.value = a.activity_type_id 
			AND ov.option_group_id = (
				SELECT id FROM civicrm_option_group WHERE name = 'activity_type' LIMIT 1
			)
		LEFT JOIN civicrm_contact c ON c.id = ul.contact_id
		WHERE ul.case_id = %1
		ORDER BY ul.created_date DESC, ul.id DESC
	";

	$params = [
		1 => [$caseId, 'Integer'],
	];

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

/**
 * Implements hook_civicrm_alterMenu().
 * Registers a route for the standalone Unit Ledger page.
 */
function unitledger_civicrm_alterMenu(&$items) {
	$items['civicrm/unit-ledger'] = [
		'page_callback' => 'CRM_UnitLedger_Page_UnitLedgerView',
		'access_arguments' => ['access CiviCRM'],
		'is_public' => 0,
	];
}

/**
 * Implements hook_civicrm_navigationMenu().
 * Adds Unit Ledger to the main navigation menu.
 */
function unitledger_civicrm_navigationMenu(&$menu) {
	_unitledger_civix_insert_navigation_menu($menu, 'Administer', [
		'label' => E::ts('Unit Ledger'),
		'name' => 'unit_ledger',
		'url' => 'civicrm/unit-ledger',
		'permission' => 'access CiviCRM',
		'operator' => 'OR',
		'separator' => 0,
		'active' => 1,
	]);
}
