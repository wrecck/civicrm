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
    $this->registerCiviRulesComponents();
  }

  /**
   * Perform actions after enable
   */
  public function postEnable() {
    $this->registerCiviRulesComponents();
  }

  /**
   * Example of a versioned upgrade step (optional)
   * Called when upgrading from version 1.0.0 to 1.1.0
   */
  public function upgrade_1100() {
    $this->registerCiviRulesComponents();
    return TRUE;
  }

  /**
   * Register CiviRules components from JSON files
   */
  private function registerCiviRulesComponents() {
    // Add debugging
    \Civi::log()->info('Unit Ledger: registerCiviRulesComponents called');
    
    // Check if CiviRules is available
    if (!class_exists('CRM_Civirules_Utils_Upgrader')) {
      \Civi::log()->warning('CiviRules not available; skipping Unit Ledger component registration.');
      return;
    }

    \Civi::log()->info('Unit Ledger: CiviRules available, registering components');

    // Register triggers, conditions, and actions
    $this->registerTriggers();
    $this->registerConditions();
    $this->registerActions();
  }

  /**
   * Register triggers from JSON file
   */
  private function registerTriggers() {
    $jsonFile = E::path('civirules_triggers.json');
    if (file_exists($jsonFile)) {
      CRM_Civirules_Utils_Upgrader::insertTriggersFromJson($jsonFile);
    }
  }

  /**
   * Register conditions from JSON file
   */
  private function registerConditions() {
    $jsonFile = E::path('civirules_conditions.json');
    if (file_exists($jsonFile)) {
      CRM_Civirules_Utils_Upgrader::insertConditionsFromJson($jsonFile);
    }
  }

  /**
   * Register actions from JSON file
   */
  private function registerActions() {
    $jsonFile = E::path('civirules_actions.json');
    \Civi::log()->info("Unit Ledger: Looking for actions JSON at {$jsonFile}");
    
    if (file_exists($jsonFile)) {
      \Civi::log()->info('Unit Ledger: Actions JSON file found, registering actions');
      try {
        CRM_Civirules_Utils_Upgrader::insertActionsFromJson($jsonFile);
        \Civi::log()->info('Unit Ledger: Actions registered successfully');
      } catch (Exception $e) {
        \Civi::log()->error('Unit Ledger: Error registering actions: ' . $e->getMessage());
      }
    } else {
      \Civi::log()->error("Unit Ledger: Actions JSON file not found at {$jsonFile}");
    }
  }
}
