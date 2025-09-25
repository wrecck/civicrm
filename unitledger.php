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
