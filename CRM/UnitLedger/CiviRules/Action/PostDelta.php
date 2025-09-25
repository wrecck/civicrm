<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Class for Unit Ledger Post Delta Action
 */
class CRM_UnitLedger_CiviRules_Action_PostDelta extends CRM_Civirules_Action {

  /**
   * Process the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    // This is where you would implement the actual Unit Ledger posting logic
    // For now, this is just a placeholder that logs the action
    $this->logAction('Unit Ledger Delta Posted', $triggerData);
  }

  /**
   * Returns a redirect url to extra data input from the user after adding an action
   *
   * @param int $ruleActionId
   * @return bool|string
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE; // No extra configuration needed
  }

  /**
   * Returns a user friendly text explaining the action params
   *
   * @return string
   */
  public function userFriendlyConditionParams() {
    return 'Post delta to Unit Ledger';
  }

  /**
   * Get help text for the action
   *
   * @param string $context
   * @return string
   */
  public function getHelpText(string $context): string {
    switch ($context) {
      case 'actionDescription':
        return E::ts('This action posts a delta to the Unit Ledger system.');
      
      case 'actionDescriptionWithParams':
        return $this->userFriendlyConditionParams();
      
      case 'actionParamsHelp':
      default:
        return E::ts('This action will post a delta to the Unit Ledger when the rule conditions are met.');
    }
  }
}
