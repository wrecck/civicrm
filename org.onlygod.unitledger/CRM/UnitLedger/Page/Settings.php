<?php

namespace CRM\UnitLedger\Page;

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Unit Ledger Settings Page
 * 
 * Provides the settings interface for the unit ledger extension.
 */
class Settings extends \CRM_Core_Page {

  /**
   * Run the page
   */
  public function run() {
    // Check permissions
    if (!CRM_Core_Permission::check('administer unit ledger')) {
      CRM_Core_Error::statusBounce(E::ts('You do not have permission to access this page.'));
    }
    
    // Set page title
    $this->assign('pageTitle', E::ts('Unit Ledger Settings'));
    
    // Get current settings
    $programMappings = Civi::settings()->get('unitledger_program_mappings');
    $unitMultipliers = Civi::settings()->get('unitledger_unit_multipliers');
    
    $programMappings = json_decode($programMappings, TRUE) ?: [];
    $unitMultipliers = json_decode($unitMultipliers, TRUE) ?: [];
    
    // Get activity types for reference
    $activityTypes = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'activity_type',
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);
    
    $activityTypeOptions = [];
    foreach ($activityTypes['values'] as $type) {
      $activityTypeOptions[$type['value']] = $type['label'];
    }
    
    // Assign variables to template
    $this->assign('programMappings', $programMappings);
    $this->assign('unitMultipliers', $unitMultipliers);
    $this->assign('activityTypes', $activityTypeOptions);
    
    // Add breadcrumb
    CRM_Utils_System::setTitle(E::ts('Unit Ledger Settings'));
    
    parent::run();
  }

}
