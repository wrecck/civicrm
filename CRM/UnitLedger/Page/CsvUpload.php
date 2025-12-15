<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Page controller for CSV upload form
 */
class CRM_UnitLedger_Page_CsvUpload extends CRM_Core_Page {

  public function run() {
    try {
      // Set page title
      CRM_Utils_System::setTitle(E::ts('FCS Authorization Upload'));

      // Handle form submission
      $formData = $this->getFormData();

      // Assign data to template
      $this->assign('formData', $formData);
      $this->assign('uploadUrl', CRM_Utils_System::url('civicrm/unitledger/csv-upload', 'reset=1'));
      
      // Use CiviCRM's standard page rendering
      parent::run();
      return;
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV Upload: Error in run(): ' . $e->getMessage());
      $this->assign('error', $e->getMessage());
      parent::run();
    }
  }

  /**
   * Override to specify the template file
   */
  public function getTemplateFileName() {
    return E::path('templates/CRM/UnitLedger/Page/CsvUpload.tpl');
  }

  /**
   * Get form data and handle CSV upload processing
   */
  private function getFormData() {
    $formData = [];
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
      $formData['submitted'] = true;
      
      // Check if file was uploaded
      if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $formData['error'] = 'Please select a valid CSV file to upload.';
        return $formData;
      }

      $uploadedFile = $_FILES['csv_file'];
      
      // Validate file type
      $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
      if ($fileExtension !== 'csv') {
        $formData['error'] = 'Please upload a CSV file (.csv extension).';
        return $formData;
      }

      // Move uploaded file to temporary location
      $tempFile = sys_get_temp_dir() . '/' . uniqid('csv_upload_') . '.csv';
      if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
        $formData['error'] = 'Failed to save uploaded file.';
        return $formData;
      }

      try {
        // Process CSV file
        $results = CRM_UnitLedger_BAO_CsvProcessor::processCsv($tempFile);
        
        // Clean up temporary file
        @unlink($tempFile);

        // Format results message
        $message = sprintf(
          'CSV processing completed: %d successful, %d created, %d updated, %d skipped.',
          $results['success'],
          $results['created'],
          $results['updated'],
          $results['skipped']
        );

        if (!empty($results['errors'])) {
          $message .= ' Errors: ' . count($results['errors']) . ' row(s) had errors.';
          $formData['errors'] = $results['errors'];
        }

        $formData['message'] = $message;
        $formData['results'] = $results;
        $formData['success'] = true;
      } catch (Exception $e) {
        @unlink($tempFile);
        $formData['error'] = 'Error processing CSV file: ' . $e->getMessage();
        CRM_Core_Error::debug_log_message('UnitLedger CSV Upload: Processing error: ' . $e->getMessage());
      }
    }
    
    return $formData;
  }
}
