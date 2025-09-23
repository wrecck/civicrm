<?php

namespace CRM\UnitLedger\Form;

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Unit Ledger Settings Form
 * 
 * Provides configuration interface for program mappings and unit multipliers.
 */
class Settings extends \CRM_Core_Form {

  /**
   * Build the form
   */
  public function buildQuickForm() {
    $this->add('hidden', 'block', 'unitledger');
    
    // Get current settings
    $programMappings = Civi::settings()->get('unitledger_program_mappings');
    $unitMultipliers = Civi::settings()->get('unitledger_unit_multipliers');
    
    $programMappings = json_decode($programMappings, TRUE) ?: [];
    $unitMultipliers = json_decode($unitMultipliers, TRUE) ?: [];
    
    // Get activity types for mapping
    $activityTypes = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'activity_type',
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);
    
    $activityTypeOptions = [];
    foreach ($activityTypes['values'] as $type) {
      $activityTypeOptions[$type['value']] = $type['label'];
    }
    
    // Program mapping section
    $this->add('text', 'program_mappings_title', E::ts('Activity Type to Program Mappings'), ['size' => 50]);
    $this->add('textarea', 'program_mappings', E::ts('Program Mappings'), [
      'rows' => 10,
      'cols' => 80,
      'placeholder' => '{"1": "FCS", "2": "FCS", "3": "FCS", "4": "WellPoint"}',
    ]);
    
    // Unit multipliers section
    $this->add('text', 'unit_multipliers_title', E::ts('Unit Multipliers'), ['size' => 50]);
    $this->add('textarea', 'unit_multipliers', E::ts('Unit Multipliers'), [
      'rows' => 10,
      'cols' => 80,
      'placeholder' => '{"FCS": 1.0, "WellPoint": 1.0}',
    ]);
    
    // Set default values
    $this->setDefaults([
      'program_mappings' => json_encode($programMappings, JSON_PRETTY_PRINT),
      'unit_multipliers' => json_encode($unitMultipliers, JSON_PRETTY_PRINT),
    ]);
    
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);
    
    // Add help text
    $this->assign('helpText', [
      'program_mappings' => E::ts('Map activity type IDs to program names. Format: {"activity_type_id": "program_name"}'),
      'unit_multipliers' => E::ts('Set unit multipliers for each program. Format: {"program_name": multiplier_value}'),
    ]);
    
    $this->assign('activityTypes', $activityTypeOptions);
    
    parent::buildQuickForm();
  }

  /**
   * Set default values
   */
  public function setDefaultValues() {
    $defaults = [];
    
    $programMappings = Civi::settings()->get('unitledger_program_mappings');
    $unitMultipliers = Civi::settings()->get('unitledger_unit_multipliers');
    
    $defaults['program_mappings'] = json_encode(json_decode($programMappings, TRUE) ?: [], JSON_PRETTY_PRINT);
    $defaults['unit_multipliers'] = json_encode(json_decode($unitMultipliers, TRUE) ?: [], JSON_PRETTY_PRINT);
    
    return $defaults;
  }

  /**
   * Add validation rules
   */
  public function addRules() {
    $this->addFormRule(['CRM_UnitLedger_Form_Settings', 'validateForm']);
  }

  /**
   * Form validation
   */
  public static function validateForm($values) {
    $errors = [];
    
    // Validate program mappings JSON
    if (!empty($values['program_mappings'])) {
      $decoded = json_decode($values['program_mappings'], TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $errors['program_mappings'] = E::ts('Invalid JSON format for program mappings: %1', [1 => json_last_error_msg()]);
      } elseif (!is_array($decoded)) {
        $errors['program_mappings'] = E::ts('Program mappings must be a JSON object');
      }
    }
    
    // Validate unit multipliers JSON
    if (!empty($values['unit_multipliers'])) {
      $decoded = json_decode($values['unit_multipliers'], TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $errors['unit_multipliers'] = E::ts('Invalid JSON format for unit multipliers: %1', [1 => json_last_error_msg()]);
      } elseif (!is_array($decoded)) {
        $errors['unit_multipliers'] = E::ts('Unit multipliers must be a JSON object');
      } else {
        // Validate that all values are numeric
        foreach ($decoded as $program => $multiplier) {
          if (!is_numeric($multiplier)) {
            $errors['unit_multipliers'] = E::ts('All unit multipliers must be numeric values');
            break;
          }
        }
      }
    }
    
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission
   */
  public function postProcess() {
    $values = $this->exportValues();
    
    // Save program mappings
    if (!empty($values['program_mappings'])) {
      $programMappings = json_decode($values['program_mappings'], TRUE);
      Civi::settings()->set('unitledger_program_mappings', json_encode($programMappings));
    }
    
    // Save unit multipliers
    if (!empty($values['unit_multipliers'])) {
      $unitMultipliers = json_decode($values['unit_multipliers'], TRUE);
      Civi::settings()->set('unitledger_unit_multipliers', json_encode($unitMultipliers));
    }
    
    CRM_Core_Session::setStatus(
      E::ts('Unit Ledger settings have been saved successfully.'),
      E::ts('Settings Saved'),
      'success'
    );
    
    parent::postProcess();
  }

}
