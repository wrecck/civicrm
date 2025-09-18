<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Upgrader class for Unit Ledger extension
 */
class CRM_UnitLedger_Upgrader extends CRM_Extension_Upgrader_Base {

  public function postInstall() {
    $this->registerCiviRulesActions();
  }

  public function postEnable() {
    $this->registerCiviRulesActions();
  }

  public function upgrade_1001() {
    $this->registerCiviRulesActions();
    return TRUE;
  }

  private function registerCiviRulesActions() {
    if (!method_exists('CRM_Civirules_Utils_Upgrader', 'insertActionsFromJson')) {
      CRM_Core_Error::debug_log_message('CiviRules not enabled (UnitLedger): skipping action registration');
      return;
    }

    $jsonFile = E::path('civirules_actions.json');
    if (file_exists($jsonFile)) {
      CRM_Civirules_Utils_Upgrader::insertActionsFromJson($jsonFile);
    } else {
      CRM_Core_Error::debug_log_message("UnitLedger: civirules_actions.json not found at {$jsonFile}");
    }
  }
}
