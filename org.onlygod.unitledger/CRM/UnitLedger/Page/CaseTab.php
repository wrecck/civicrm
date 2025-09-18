<?php

namespace CRM\UnitLedger\Page;

use CRM_UnitLedger_ExtensionUtil as E;
use CRM_UnitLedger_BAO_UnitLedger as UnitLedgerBAO;

/**
 * Case Tab Page for Units Ledger
 * 
 * Displays unit ledger entries for a specific case.
 */
class CaseTab extends \CRM_Core_Page {

  /**
   * Run the page
   */
  public function run() {
    // Check permissions
    if (!CRM_Core_Permission::check('view unit ledger')) {
      CRM_Core_Error::statusBounce(E::ts('You do not have permission to access this page.'));
    }
    
    // Get case ID from URL
    $caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this, TRUE);
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    
    // Set page title
    $this->assign('pageTitle', E::ts('Units Ledger - Case %1', [1 => $caseId]));
    
    // Get case information
    $case = civicrm_api3('Case', 'getsingle', [
      'id' => $caseId,
      'return' => ['subject', 'case_type_id', 'status_id'],
    ]);
    
    $this->assign('case', $case);
    $this->assign('caseId', $caseId);
    $this->assign('contactId', $contactId);
    
    // Get current balances by program
    $currentBalances = UnitLedgerBAO::getCaseBalancesByProgram($caseId);
    $this->assign('currentBalances', $currentBalances);
    
    // Get ledger entries
    $ledgerEntries = $this->getLedgerEntries($caseId);
    $this->assign('ledgerEntries', $ledgerEntries);
    
    // Get summary statistics
    $summary = $this->getSummaryStatistics($caseId);
    $this->assign('summary', $summary);
    
    // Add breadcrumb
    CRM_Utils_System::setTitle(E::ts('Units Ledger - %1', [1 => $case['subject']]));
    
    parent::run();
  }

  /**
   * Get ledger entries for the case
   */
  private function getLedgerEntries($caseId) {
    $dao = new UnitLedgerBAO();
    $dao->case_id = $caseId;
    $dao->orderBy('transaction_date DESC, id DESC');
    $dao->find();
    
    $entries = [];
    while ($dao->fetch()) {
      $entries[] = [
        'id' => $dao->id,
        'activity_id' => $dao->activity_id,
        'program' => $dao->program,
        'units' => $dao->units,
        'unit_type' => $dao->unit_type,
        'transaction_date' => $dao->transaction_date,
        'balance' => $dao->balance,
        'transaction_type' => $dao->transaction_type,
        'description' => $dao->description,
        'created_date' => $dao->created_date,
      ];
    }
    
    return $entries;
  }

  /**
   * Get summary statistics for the case
   */
  private function getSummaryStatistics($caseId) {
    $dao = new UnitLedgerBAO();
    $dao->case_id = $caseId;
    $dao->find();
    
    $summary = [
      'total_entries' => 0,
      'total_units' => 0,
      'programs' => [],
      'date_range' => ['start' => NULL, 'end' => NULL],
    ];
    
    while ($dao->fetch()) {
      $summary['total_entries']++;
      $summary['total_units'] += $dao->units;
      
      if (!isset($summary['programs'][$dao->program])) {
        $summary['programs'][$dao->program] = [
          'entries' => 0,
          'units' => 0,
          'balance' => 0,
        ];
      }
      
      $summary['programs'][$dao->program]['entries']++;
      $summary['programs'][$dao->program]['units'] += $dao->units;
      $summary['programs'][$dao->program]['balance'] = $dao->balance;
      
      if (!$summary['date_range']['start'] || $dao->transaction_date < $summary['date_range']['start']) {
        $summary['date_range']['start'] = $dao->transaction_date;
      }
      if (!$summary['date_range']['end'] || $dao->transaction_date > $summary['date_range']['end']) {
        $summary['date_range']['end'] = $dao->transaction_date;
      }
    }
    
    return $summary;
  }

}
