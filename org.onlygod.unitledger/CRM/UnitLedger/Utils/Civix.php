<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_UnitLedger_Upgrader extends CRM_UnitLedger_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    $this->executeSqlFile('sql/auto_install.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * that here to avoid order of operation problems.
   */
  public function postInstall() {
    // Set default configuration
    $this->setDefaultConfiguration();
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  public function uninstall() {
    $this->executeSqlFile('sql/auto_uninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  public function enable() {
    // Enable any managed entities
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
  }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  public function disable() {
    // Disable any managed entities
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
  }

  /**
   * Set default configuration values.
   */
  private function setDefaultConfiguration() {
    // Set default program mappings
    $defaultMappings = [
      '1' => 'FCS', // FCS Authorization
      '2' => 'FCS', // FCS Delivered
      '3' => 'FCS', // FCS Adjustment
      '4' => 'WellPoint', // WellPoint Authorization
    ];
    
    Civi::settings()->set('unitledger_program_mappings', json_encode($defaultMappings));
    
    // Set default unit multipliers
    $defaultMultipliers = [
      'FCS' => 1.0,
      'WellPoint' => 1.0,
    ];
    
    Civi::settings()->set('unitledger_unit_multipliers', json_encode($defaultMultipliers));
  }

}

/**
 * Base class for upgrader.
 */
abstract class CRM_UnitLedger_Upgrader_Base {

  /**
   * @var string
   */
  protected $extensionName;

  /**
   * @var string
   */
  protected $extensionDir;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->extensionName = 'org.onlygod.unitledger';
    $this->extensionDir = E::path();
  }

  /**
   * Run a SQL file.
   *
   * @param string $relativePath
   *   Path relative to the extension directory.
   */
  protected function executeSqlFile($relativePath) {
    $sqlFile = $this->extensionDir . DIRECTORY_SEPARATOR . $relativePath;
    if (!file_exists($sqlFile)) {
      return;
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === FALSE) {
      throw new CRM_Core_Exception("Cannot read SQL file: $sqlFile");
    }

    // Split the SQL into individual statements.
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
      if (empty($statement)) {
        continue;
      }
      CRM_Core_DAO::executeQuery($statement);
    }
  }

  /**
   * Add a managed entity.
   *
   * @param string $entity
   *   Entity type.
   * @param string $name
   *   Entity name.
   * @param array $params
   *   Entity parameters.
   */
  protected function addManagedEntity($entity, $name, $params) {
    $params['module'] = $this->extensionName;
    $params['name'] = $name;
    CRM_Core_ManagedEntities::singleton(TRUE)->create($entity, $params);
  }

  /**
   * Remove a managed entity.
   *
   * @param string $entity
   *   Entity type.
   * @param string $name
   *   Entity name.
   */
  protected function removeManagedEntity($entity, $name) {
    CRM_Core_ManagedEntities::singleton(TRUE)->delete($entity, $name);
  }

}

/**
 * Civix helper functions.
 */
function _org_onlygod_unitledger_civix_civicrm_config(&$config) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $template = CRM_Core_Smarty::singleton();

  $extRoot = E::path();
  $extDir = $extRoot . DIRECTORY_SEPARATOR;

  if (is_dir($extDir . 'templates')) {
    $template->addTemplateDir($extDir . 'templates');
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
}

function _org_onlygod_unitledger_civix_civicrm_install() {
  _org_onlygod_unitledger_civix_civicrm_config();
  if ($upgrader = _org_onlygod_unitledger_civix_upgrader()) {
    $upgrader->install();
  }
}

function _org_onlygod_unitledger_civix_civicrm_enable() {
  _org_onlygod_unitledger_civix_civicrm_config();
  if ($upgrader = _org_onlygod_unitledger_civix_upgrader()) {
    $upgrader->enable();
  }
}

function _org_onlygod_unitledger_civix_civicrm_disable() {
  _org_onlygod_unitledger_civix_civicrm_config();
  if ($upgrader = _org_onlygod_unitledger_civix_upgrader()) {
    $upgrader->disable();
  }
}

function _org_onlygod_unitledger_civix_civicrm_uninstall() {
  _org_onlygod_unitledger_civix_civicrm_config();
  if ($upgrader = _org_onlygod_unitledger_civix_upgrader()) {
    $upgrader->uninstall();
  }
}

function _org_onlygod_unitledger_civix_civicrm_managed(&$entities) {
  $mgdFiles = _org_onlygod_unitledger_civix_find_files(__DIR__, '*.mgd.php');
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = E::key();
      }
      $entities[] = $e;
    }
  }
}

function _org_onlygod_unitledger_civix_civicrm_entityTypes(&$entityTypes) {
  $entityTypes = array_merge($entityTypes, [
    'UnitLedger' => [
      'name' => 'UnitLedger',
      'class' => 'CRM_UnitLedger_DAO_UnitLedger',
      'table' => 'civicrm_unit_ledger',
    ],
  ]);
}

function _org_onlygod_unitledger_civix_civicrm_caseTypes(&$caseTypes) {
  $caseTypes = array_merge($caseTypes, [
    'UnitLedger' => [
      'module' => E::key(),
      'name' => 'UnitLedger',
      'file' => E::path('xml/case/UnitLedger.xml'),
    ],
  ]);
}

function _org_onlygod_unitledger_civix_civicrm_alterSettingsFolders(&$metaDataFolders) {
  $metaDataFolders[] = E::path('settings');
}

function _org_onlygod_unitledger_civix_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = [
      'attributes' => array_merge([
        'label' => $item['label'],
        'active' => 1,
      ], $item),
    ];
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = array_slice($path, 1);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $path[0]) {
        if (!$entry['child']) {
          $entry['child'] = [];
        }
        $found = _org_onlygod_unitledger_civix_insert_navigation_menu($entry['child'], $path, $item, $key);
      }
    }
    return $found;
  }
}

function _org_onlygod_unitledger_civix_navigationMenu(&$menu) {
  _org_onlygod_unitledger_civix_navigationMenu_recurse($menu);
}

function _org_onlygod_unitledger_civix_navigationMenu_recurse(&$menu) {
  $maxNavID = 1;
  array_walk_recursive($menu, function($item, $key) use (&$maxNavID) {
    if ($key === 'navID') {
      $maxNavID = max($maxNavID, $item);
    }
  });
  _org_onlygod_unitledger_civix_fixNavigationMenuItems($menu, $maxNavID, NULL);
}

function _org_onlygod_unitledger_civix_fixNavigationMenuItems(&$menu, $maxNavID, $parentID) {
  $keysToRemove = [];
  $myNavID = $maxNavID;
  foreach ($menu as $key => &$entry) {
    if (!array_key_exists('navID', $entry['attributes'])) {
      $maxNavID++;
      $entry['attributes']['navID'] = $maxNavID;
    }
    if ($entry['attributes']['navID'] > $myNavID) {
      $myNavID = $entry['attributes']['navID'];
    }
    if (array_key_exists('child', $entry)) {
      $entry['attributes']['parentID'] = $entry['attributes']['navID'];
      _org_onlygod_unitledger_civix_fixNavigationMenuItems($entry['child'], $myNavID, $entry['attributes']['navID']);
    }
  }
  foreach ($keysToRemove as $key) {
    unset($menu[$key]);
  }
}

function _org_onlygod_unitledger_civix_upgrader() {
  if (!file_exists(__DIR__ . '/CRM/UnitLedger/Upgrader.php')) {
    return NULL;
  }
  else {
    return new CRM_UnitLedger_Upgrader();
  }
}

function _org_onlygod_unitledger_civix_find_files($dir, $pattern) {
  if (is_callable(['CRM_Utils_File', 'findFiles'])) {
    return CRM_Utils_File::findFiles($dir, $pattern);
  }
  else {
    $todos = [$dir];
    $result = [];
    while (!empty($todos)) {
      $subdir = array_shift($todos);
      foreach (glob("$subdir/$pattern") as $match) {
        if (!is_dir($match)) {
          $result[] = $match;
        }
      }
      $dh = opendir($subdir);
      while (FALSE !== ($entry = readdir($dh))) {
        $path = $subdir . DIRECTORY_SEPARATOR . $entry;
        if ($entry[0] == '.') {
        }
        elseif (is_dir($path)) {
          $todos[] = $path;
        }
      }
      closedir($dh);
    }
    return $result;
  }
}
