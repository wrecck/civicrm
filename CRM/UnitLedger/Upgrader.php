<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Upgrader class for Unit Ledger extension
 */
class CRM_UnitLedger_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Perform actions after install
   */
  public function postInstall() {
    $this->registerCiviRulesActions();
  }

  /**
   * Perform actions after enable
   */
  public function postEnable() {
    $this->registerCiviRulesActions();
  }

  /**
   * Register CiviRules actions from JSON file
   */
  private function registerCiviRulesActions() {
    if (!method_exists('CRM_Civirules_Utils_Upgrader', 'insertActionsFromJson')) {
      throw new Exception('Method CRM_Civirules_Utils_Upgrader::insertActionsFromJson() not found. Is the CiviRules extension enabled?');
    }
    
    $jsonFile = E::path('civirules_actions.json');
    if (file_exists($jsonFile)) {
      CRM_Civirules_Utils_Upgrader::insertActionsFromJson($jsonFile);
    }
  }
}
