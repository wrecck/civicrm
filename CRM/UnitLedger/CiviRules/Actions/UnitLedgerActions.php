<?php

namespace CRM\UnitLedger\CiviRules\Actions;

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Unit Ledger CiviRules Actions Provider
 * 
 * Registers CiviRules actions for the unit ledger extension.
 */
class UnitLedgerActions {

  /**
   * Register actions
   */
  public static function register() {
    // Check if CiviRules is available
    if (!class_exists('CRM_Civirules_Utils_Hook')) {
      return;
    }
    
    // Register the PostDelta action using the correct CiviRules hook
    $hook = \CRM_Civirules_Utils_Hook::singleton();
    $hook->hook_civirules_registerActions([
      'unitledger_post_delta' => [
        'label' => E::ts('Post Delta to Unit Ledger'),
        'class' => 'CRM_UnitLedger_CiviRules_Actions_PostDelta',
        'description' => E::ts('Posts unit changes to the ledger in a delta-aware manner'),
      ],
    ]);
  }

}
