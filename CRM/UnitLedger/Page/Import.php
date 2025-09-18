<?php

namespace CRM\UnitLedger\Page;

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Unit Ledger Import Page
 * 
 * Provides the CSV import interface for the unit ledger extension.
 */
class Import extends \CRM_Core_Page {

  /**
   * Run the page
   */
  public function run() {
    // Check permissions
    if (!CRM_Core_Permission::check('import unit ledger')) {
      CRM_Core_Error::statusBounce(E::ts('You do not have permission to access this page.'));
    }
    
    // Set page title
    $this->assign('pageTitle', E::ts('Import Unit Ledger Data'));
    
    // Get case ID from URL if provided
    $caseId = CRM_Utils_Request::retrieve('case_id', 'Positive', $this, FALSE);
    if ($caseId) {
      $this->assign('caseId', $caseId);
      
      // Get case information
      $case = civicrm_api3('Case', 'getsingle', [
        'id' => $caseId,
        'return' => ['subject', 'case_type_id', 'status_id'],
      ]);
      $this->assign('case', $case);
    }
    
    // Add breadcrumb
    CRM_Utils_System::setTitle(E::ts('Import Unit Ledger Data'));
    
    parent::run();
  }

}
