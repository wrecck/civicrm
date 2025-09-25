<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_UnitLedger_ExtensionUtil {
  const SHORT_NAME = 'unitledger';
  const LONG_NAME = 'org.onlygod.unitledger';
  const CLASS_PREFIX = 'CRM_UnitLedger';

  /**
   * Translate a string using the extension's domain.
   */
  public static function ts($text, $params = []): string {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = [self::LONG_NAME, NULL];
    }
    return ts($text, $params);
  }

  /**
   * Get the URL of a resource file (in this extension).
   */
  public static function url($file = NULL): string {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
  }

  /**
   * Get the path of a resource file (in this extension).
   */
  public static function path($file = NULL) {
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the name of a class within this extension.
   */
  public static function findClass($suffix) {
    return self::CLASS_PREFIX . '_' . str_replace('\\', '_', $suffix);
  }
}

use CRM_UnitLedger_ExtensionUtil as E;

spl_autoload_register('_unitledger_civix_class_loader', TRUE, TRUE);

function _unitledger_civix_class_loader($class) {
  // This allows us to tap-in to the installation process
  if (strpos($class, 'CRM_UnitLedger_') === 0) {
    $file = E::path(str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php');
    if (file_exists($file)) {
      require_once $file;
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_config().
 */
function _unitledger_civix_civicrm_config($config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $extRoot = __DIR__ . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
}

/**
 * Implements hook_civicrm_install().
 */
function _unitledger_civix_civicrm_install() {
  _unitledger_civix_civicrm_config();
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 */
function _unitledger_civix_civicrm_enable(): void {
  _unitledger_civix_civicrm_config();
}
