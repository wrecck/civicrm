<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * CiviRules Action: Post Delta to Unit Ledger
 */
class CRM_UnitLedger_CiviRules_Action_PostDelta extends CRM_Civirules_Action {

  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE;
  }

  public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData) {
    $activity = $triggerData->getEntityData('Activity');
    if (empty($activity['id'])) {
      return;
    }
    $activityId = $activity['id'];

    try {
      $result = civicrm_api4('UnitLedger', 'postDelta', [
        'activity_id' => $activityId,
      ]);
      if (!empty($result['status']) && $result['status'] === 'posted') {
        \Civi::log()->info('UnitLedger: Posted delta for activity {id}', ['id' => $activityId]);
      } elseif (!empty($result['status']) && $result['status'] === 'skipped') {
        \Civi::log()->info('UnitLedger: Skipped posting delta for activity {id}', ['id' => $activityId]);
      }
    } catch (\Exception $e) {
      \Civi::log()->error('UnitLedger: Exception for activity {id}: {error}', [
        'id' => $activityId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  public function userFriendlyConditionParams() {
    return E::ts('Post Delta to Unit Ledger');
  }

  public function doesWorkWithTrigger(\CRM_Civirules_Trigger $trigger, \CRM_Civirules_BAO_Rule $rule) {
    return $trigger->doesProvideEntity('Activity');
  }
}
