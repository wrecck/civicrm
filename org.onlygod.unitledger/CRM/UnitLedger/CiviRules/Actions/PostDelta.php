<?php

namespace CRM\UnitLedger\CiviRules\Actions;

use CRM_UnitLedger_ExtensionUtil as E;
use CRM_UnitLedger_BAO_UnitLedger as UnitLedgerBAO;

/**
 * CiviRules Action: Post Delta to Unit Ledger
 * 
 * This action posts unit changes to the ledger in a delta-aware manner.
 */
class PostDelta extends \CRM_Civirules_Action {

  /**
   * Process the action
   */
  public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $activity = $triggerData->getEntityData('Activity');
    
    if (!$activity || !isset($activity['id'])) {
      return;
    }
    
    $activityId = $activity['id'];
    
    try {
      // Use the APIv4 to post delta
      $result = civicrm_api4('UnitLedger', 'postDelta', [
        'activity_id' => $activityId,
      ]);
      
      // Log the result
      if ($result['status'] === 'posted') {
        \Civi::log()->info('UnitLedger: Posted delta for activity {id}: {delta} units', [
          'id' => $activityId,
          'delta' => $result['delta'],
        ]);
      } elseif ($result['status'] === 'skipped') {
        \Civi::log()->info('UnitLedger: Skipped activity {id}: {reason}', [
          'id' => $activityId,
          'reason' => $result['reason'],
        ]);
      }
      
    } catch (Exception $e) {
      \Civi::log()->error('UnitLedger: Error posting delta for activity {id}: {error}', [
        'id' => $activityId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Returns condition data as an array and ready for import.
   * E.g. replace labels for fields.
   */
  public function exportActionParameters(\CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $actionParams = $this->getActionParameters();
    return $actionParams;
  }

  /**
   * Returns condition data as an array and ready for import.
   * E.g. replace field ids with field labels
   */
  public function importActionParameters($actionParams = NULL) {
    return $actionParams;
  }

  /**
   * Returns a user friendly text explaining the condition params
   * e.g. an email address or user name.
   */
  public function userFriendlyConditionParams() {
    return E::ts('Post unit delta to ledger');
  }

  /**
   * This function validates whether this action works with the selected trigger.
   *
   * This function could be overridden in child classes to provide additional validation
   * whether an action is possible in the current setup.
   *
   * @param \CRM_Civirules_Trigger $trigger
   * @param \CRM_Civirules_BAO_Rule $rule
   * @return bool
   */
  public function doesWorkWithTrigger(\CRM_Civirules_Trigger $trigger, \CRM_Civirules_BAO_Rule $rule) {
    return $trigger->doesProvideEntity('Activity');
  }

  /**
   * Returns a redirect url to extra data input from the user after adding a action
   *
   * Return false if you do not need extra data input
   *
   * @param int $ruleActionId
   * @return bool|string
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE;
  }

}
