<?php

namespace CRM\UnitLedger\Form;

use CRM_UnitLedger_ExtensionUtil as E;
use CRM_UnitLedger_BAO_UnitLedger as UnitLedgerBAO;

/**
 * Unit Ledger CSV Import Form
 * 
 * Handles CSV import of unit ledger data with dry-run and duplicate handling.
 */
class Import extends \CRM_Core_Form {

  /**
   * Build the form
   */
  public function buildQuickForm() {
    $this->add('hidden', 'block', 'unitledger');
    
    // File upload
    $this->add('file', 'uploadFile', E::ts('Import Data File (CSV)'), 'size=30 maxlength=255', TRUE);
    $this->setMaxFileSize(10485760); // 10MB
    
    // Import options
    $this->add('checkbox', 'dry_run', E::ts('Dry Run (Preview Only)'));
    $this->add('checkbox', 'skip_duplicates', E::ts('Skip Duplicates'));
    $this->add('checkbox', 'create_activities', E::ts('Create FCS Authorization Activities'));
    
    // Case selection (optional)
    $this->addEntityRef('case_id', E::ts('Case'), [
      'entity' => 'Case',
      'placeholder' => E::ts('- Select Case -'),
      'select' => ['minimumInputLength' => 0],
    ]);
    
    // Date range for import
    $this->addDate('start_date', E::ts('Start Date'), FALSE, ['formatType' => 'activityDateTime']);
    $this->addDate('end_date', E::ts('End Date'), FALSE, ['formatType' => 'activityDateTime']);
    
    // Add buttons
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Import'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);
    
    // Set default values
    $this->setDefaults([
      'dry_run' => 1,
      'skip_duplicates' => 1,
      'create_activities' => 1,
    ]);
    
    parent::buildQuickForm();
  }

  /**
   * Add validation rules
   */
  public function addRules() {
    $this->addFormRule(['CRM_UnitLedger_Form_Import', 'validateForm']);
  }

  /**
   * Form validation
   */
  public static function validateForm($values) {
    $errors = [];
    
    // Validate file upload
    if (empty($values['uploadFile']['name'])) {
      $errors['uploadFile'] = E::ts('Please select a CSV file to import.');
    } else {
      $fileInfo = pathinfo($values['uploadFile']['name']);
      if (strtolower($fileInfo['extension']) !== 'csv') {
        $errors['uploadFile'] = E::ts('Please upload a CSV file.');
      }
    }
    
    // Validate date range
    if (!empty($values['start_date']) && !empty($values['end_date'])) {
      $startDate = strtotime($values['start_date']);
      $endDate = strtotime($values['end_date']);
      if ($startDate > $endDate) {
        $errors['end_date'] = E::ts('End date must be after start date.');
      }
    }
    
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission
   */
  public function postProcess() {
    $values = $this->exportValues();
    
    try {
      // Process the CSV file
      $result = $this->processCsvFile($values);
      
      if ($values['dry_run']) {
        // Show preview results
        $this->assign('previewResults', $result);
        $this->assign('isDryRun', TRUE);
      } else {
        // Show import results
        $this->assign('importResults', $result);
        $this->assign('isDryRun', FALSE);
        
        CRM_Core_Session::setStatus(
          E::ts('Import completed successfully. %1 records processed, %2 created, %3 updated, %4 skipped.', [
            1 => $result['total'],
            2 => $result['created'],
            3 => $result['updated'],
            4 => $result['skipped'],
          ]),
          E::ts('Import Complete'),
          'success'
        );
      }
      
    } catch (Exception $e) {
      CRM_Core_Session::setStatus(
        E::ts('Import failed: %1', [1 => $e->getMessage()]),
        E::ts('Import Error'),
        'error'
      );
    }
    
    parent::postProcess();
  }

  /**
   * Process the CSV file
   */
  private function processCsvFile($values) {
    $file = $values['uploadFile'];
    $dryRun = $values['dry_run'];
    $skipDuplicates = $values['skip_duplicates'];
    $createActivities = $values['create_activities'];
    $caseId = $values['case_id'] ?? NULL;
    $startDate = $values['start_date'] ?? NULL;
    $endDate = $values['end_date'] ?? NULL;
    
    // Read CSV file
    $csvData = $this->readCsvFile($file['tmp_name']);
    
    $result = [
      'total' => 0,
      'created' => 0,
      'updated' => 0,
      'skipped' => 0,
      'errors' => [],
      'records' => [],
    ];
    
    foreach ($csvData as $rowIndex => $row) {
      $result['total']++;
      
      try {
        // Validate row data
        $validatedRow = $this->validateRowData($row, $rowIndex);
        
        if (!$validatedRow) {
          $result['skipped']++;
          continue;
        }
        
        // Check for duplicates if enabled
        if ($skipDuplicates && $this->isDuplicate($validatedRow)) {
          $result['skipped']++;
          $result['records'][] = [
            'row' => $rowIndex + 1,
            'status' => 'skipped',
            'reason' => 'Duplicate record',
            'data' => $validatedRow,
          ];
          continue;
        }
        
        // Apply date range filter
        if ($startDate && $validatedRow['transaction_date'] < $startDate) {
          $result['skipped']++;
          continue;
        }
        if ($endDate && $validatedRow['transaction_date'] > $endDate) {
          $result['skipped']++;
          continue;
        }
        
        // Set case ID if provided
        if ($caseId) {
          $validatedRow['case_id'] = $caseId;
        }
        
        if (!$dryRun) {
          // Create activity if requested
          if ($createActivities && $validatedRow['transaction_type'] === 'import') {
            $activityId = $this->createFCSAuthorizationActivity($validatedRow);
            $validatedRow['activity_id'] = $activityId;
          }
          
          // Create ledger entry
          $ledgerEntry = civicrm_api4('UnitLedger', 'create', ['values' => $validatedRow]);
          $result['created']++;
          
          $result['records'][] = [
            'row' => $rowIndex + 1,
            'status' => 'created',
            'ledger_id' => $ledgerEntry->first()['id'],
            'data' => $validatedRow,
          ];
        } else {
          // Dry run - just validate
          $result['records'][] = [
            'row' => $rowIndex + 1,
            'status' => 'valid',
            'data' => $validatedRow,
          ];
        }
        
      } catch (Exception $e) {
        $result['errors'][] = [
          'row' => $rowIndex + 1,
          'error' => $e->getMessage(),
          'data' => $row,
        ];
        $result['skipped']++;
      }
    }
    
    return $result;
  }

  /**
   * Read CSV file
   */
  private function readCsvFile($filePath) {
    $data = [];
    $handle = fopen($filePath, 'r');
    
    if ($handle === FALSE) {
      throw new Exception('Could not open CSV file');
    }
    
    // Read header row
    $headers = fgetcsv($handle);
    if ($headers === FALSE) {
      throw new Exception('Could not read CSV headers');
    }
    
    // Read data rows
    while (($row = fgetcsv($handle)) !== FALSE) {
      $data[] = array_combine($headers, $row);
    }
    
    fclose($handle);
    return $data;
  }

  /**
   * Validate row data
   */
  private function validateRowData($row, $rowIndex) {
    $requiredFields = ['program', 'units', 'transaction_date'];
    
    foreach ($requiredFields as $field) {
      if (empty($row[$field])) {
        throw new Exception("Missing required field: {$field}");
      }
    }
    
    // Validate units is numeric
    if (!is_numeric($row['units'])) {
      throw new Exception("Units must be numeric: {$row['units']}");
    }
    
    // Validate date format
    $date = strtotime($row['transaction_date']);
    if ($date === FALSE) {
      throw new Exception("Invalid date format: {$row['transaction_date']}");
    }
    
    // Set defaults
    $validatedRow = [
      'program' => trim($row['program']),
      'units' => (float) $row['units'],
      'unit_type' => $row['unit_type'] ?? 'hours',
      'transaction_date' => date('Y-m-d H:i:s', $date),
      'transaction_type' => $row['transaction_type'] ?? 'import',
      'description' => $row['description'] ?? "Imported from CSV - Row {$rowIndex}",
      'contact_id' => $row['contact_id'] ?? NULL,
      'case_id' => $row['case_id'] ?? NULL,
    ];
    
    return $validatedRow;
  }

  /**
   * Check if record is duplicate
   */
  private function isDuplicate($row) {
    // Create hash of key fields
    $hash = md5(serialize([
      'program' => $row['program'],
      'units' => $row['units'],
      'transaction_date' => $row['transaction_date'],
      'contact_id' => $row['contact_id'],
      'case_id' => $row['case_id'],
    ]));
    
    // Check if hash exists in database
    $dao = new UnitLedgerBAO();
    $dao->whereAdd("MD5(CONCAT(program, units, transaction_date, COALESCE(contact_id, ''), COALESCE(case_id, ''))) = '" . CRM_Utils_Type::escape($hash, 'String') . "'");
    $dao->find(TRUE);
    
    return $dao->id ? TRUE : FALSE;
  }

  /**
   * Create FCS Authorization activity
   */
  private function createFCSAuthorizationActivity($row) {
    $activity = civicrm_api3('Activity', 'create', [
      'activity_type_id' => 'FCS Authorization', // This should be mapped to actual activity type ID
      'subject' => "FCS Authorization - {$row['program']} - {$row['units']} units",
      'activity_date_time' => $row['transaction_date'],
      'status_id' => 'Completed',
      'target_contact_id' => $row['contact_id'],
      'case_id' => $row['case_id'],
      'details' => "Created from CSV import: {$row['description']}",
    ]);
    
    return $activity['id'];
  }

}
