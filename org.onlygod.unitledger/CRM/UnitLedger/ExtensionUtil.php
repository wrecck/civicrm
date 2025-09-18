<?php

namespace CRM_UnitLedger_ExtensionUtil;

/**
 * Utility functions for the UnitLedger extension.
 */
class ExtensionUtil {

  /**
   * Get the extension's directory path.
   *
   * @return string
   */
  public static function path($file = NULL) {
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the extension's URL.
   *
   * @return string
   */
  public static function url($file = NULL) {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl('org.onlygod.unitledger'), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl('org.onlygod.unitledger', $file);
  }

  /**
   * Get the extension's key.
   *
   * @return string
   */
  public static function key() {
    return 'org.onlygod.unitledger';
  }

  /**
   * Get the extension's name.
   *
   * @return string
   */
  public static function name() {
    return 'OnlyGod Unit Ledger';
  }

  /**
   * Get the extension's version.
   *
   * @return string
   */
  public static function version() {
    return '1.0.0';
  }

  /**
   * Translate a string using the extension's domain.
   *
   * @param string $text
   *   Text to translate.
   * @param array $params
   *   Parameters for the translation.
   *
   * @return string
   *   Translated text.
   */
  public static function ts($text, $params = []) {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = [self::key(), NULL];
    }
    return ts($text, $params);
  }

  /**
   * Get the extension's resource URL.
   *
   * @param string $file
   *   File path relative to the extension's resources directory.
   *
   * @return string
   *   Resource URL.
   */
  public static function resourceUrl($file) {
    return self::url('resources/' . $file);
  }

}
