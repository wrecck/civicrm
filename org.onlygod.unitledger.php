<?php
/**
 * @file
 * OnlyGod Unit Ledger Extension
 *
 * This extension provides an append-only unit ledger system that integrates
 * with CiviCRM activities to track unit transactions and maintain running balances.
 */

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 */
function org_onlygod_unitledger_civicrm_config(&$config) {
  _org_onlygod_unitledger_civix_civicrm_config($config);
  
  // Register routes
  \CRM\UnitLedger\Config\Route::register();
  
  // Register CiviRules actions (if CiviRules is available)
  if (class_exists('CRM_Civirules_Utils_Hook')) {
    \CRM\UnitLedger\CiviRules\Actions\UnitLedgerActions::register();
  }
}

/**
 * Implements hook_civicrm_install().
 */
function org_onlygod_unitledger_civicrm_install() {
  _org_onlygod_unitledger_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 */
function org_onlygod_unitledger_civicrm_enable() {
  _org_onlygod_unitledger_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 */
function org_onlygod_unitledger_civicrm_disable() {
  _org_onlygod_unitledger_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_uninstall().
 */
function org_onlygod_unitledger_civicrm_uninstall() {
  _org_onlygod_unitledger_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_managed().
 */
function org_onlygod_unitledger_civicrm_managed(&$entities) {
  _org_onlygod_unitledger_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_entityTypes().
 */
function org_onlygod_unitledger_civicrm_entityTypes(&$entityTypes) {
  _org_onlygod_unitledger_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function org_onlygod_unitledger_civicrm_navigationMenu(&$menu) {
  _org_onlygod_unitledger_civix_insert_navigation_menu($menu, 'Administer/CiviCRM', [
    'label' => E::ts('Unit Ledger Settings'),
    'name' => 'unit_ledger_settings',
    'url' => 'civicrm/admin/unitledger/settings',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _org_onlygod_unitledger_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_caseTypes().
 */
function org_onlygod_unitledger_civicrm_caseTypes(&$caseTypes) {
  _org_onlygod_unitledger_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 */
function org_onlygod_unitledger_civicrm_alterSettingsFolders(&$metaDataFolders) {
  _org_onlygod_unitledger_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_alterSettingsMetaData().
 */
function org_onlygod_unitledger_civicrm_alterSettingsMetaData(&$settingsMetadata, $domainID, $profile) {
  // Add our custom settings
  $settingsMetadata['unitledger_program_mappings'] = [
    'group_name' => 'Unit Ledger Preferences',
    'group' => 'unitledger',
    'name' => 'unitledger_program_mappings',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'Text',
    'default' => '{}',
    'add' => '5.0',
    'title' => E::ts('Activity Type to Program Mappings'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('JSON mapping of activity types to program names'),
    'help_text' => E::ts('Define which activity types correspond to which programs for unit tracking'),
  ];
  
  $settingsMetadata['unitledger_unit_multipliers'] = [
    'group_name' => 'Unit Ledger Preferences',
    'group' => 'unitledger',
    'name' => 'unitledger_unit_multipliers',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'Text',
    'default' => '{}',
    'add' => '5.0',
    'title' => E::ts('Unit Multipliers'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('JSON mapping of unit types to their multipliers'),
    'help_text' => E::ts('Define multipliers for different unit types (e.g., hours, days, sessions)'),
  ];
}

/**
 * Implements hook_civicrm_tabs().
 */
function org_onlygod_unitledger_civicrm_tabs(&$tabs, $contactID) {
  // Add Units Ledger tab to case view
  $tabs[] = [
    'id' => 'unitledger',
    'url' => CRM_Utils_System::url('civicrm/case/unitledger', "reset=1&cid={$contactID}&caseid=%%caseid%%"),
    'title' => E::ts('Units Ledger'),
    'weight' => 100,
    'count' => FALSE,
  ];
}

/**
 * Implements hook_civicrm_permission().
 */
function org_onlygod_unitledger_civicrm_permission(&$permissions) {
  $permissions['administer unit ledger'] = [
    'title' => E::ts('Administer Unit Ledger'),
    'description' => E::ts('Full access to unit ledger administration'),
  ];
  $permissions['view unit ledger'] = [
    'title' => E::ts('View Unit Ledger'),
    'description' => E::ts('View unit ledger entries and reports'),
  ];
  $permissions['import unit ledger'] = [
    'title' => E::ts('Import Unit Ledger Data'),
    'description' => E::ts('Import CSV data into unit ledger'),
  ];
}

