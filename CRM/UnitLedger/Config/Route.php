<?php

namespace CRM\UnitLedger\Config;

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Route configuration for Unit Ledger extension
 */
class Route {

  /**
   * Register routes
   */
  public static function register() {
    // Settings page
    \Civi::dispatcher()->addListener('civi.routing', function($e) {
      $e->addRoute('civicrm/admin/unitledger/settings', [
        'page_callback' => 'CRM_UnitLedger_Page_Settings',
        'access_callback' => 'CRM_Core_Permission::check',
        'access_arguments' => ['administer unit ledger'],
        'title' => E::ts('Unit Ledger Settings'),
        'description' => E::ts('Configure unit ledger settings and mappings'),
      ]);
    });
    
    // Case tab for Units Ledger
    \Civi::dispatcher()->addListener('civi.routing', function($e) {
      $e->addRoute('civicrm/case/unitledger', [
        'page_callback' => 'CRM_UnitLedger_Page_CaseTab',
        'access_callback' => 'CRM_Core_Permission::check',
        'access_arguments' => ['view unit ledger'],
        'title' => E::ts('Units Ledger'),
        'description' => E::ts('View unit ledger entries for this case'),
      ]);
    });
    
    // CSV Import page
    \Civi::dispatcher()->addListener('civi.routing', function($e) {
      $e->addRoute('civicrm/admin/unitledger/import', [
        'page_callback' => 'CRM_UnitLedger_Page_Import',
        'access_callback' => 'CRM_Core_Permission::check',
        'access_arguments' => ['import unit ledger'],
        'title' => E::ts('Import Unit Ledger Data'),
        'description' => E::ts('Import CSV data into unit ledger'),
      ]);
    });
  }

}
