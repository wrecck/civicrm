<?php
// Test script to manually run the Unit Ledger menu creation
require_once 'C:\xampp\htdocs\projects\onlygod2\civicrm.settings.php';
require_once 'C:\xampp\htdocs\projects\onlygod2\CRM\Core\Config.php';

$config = CRM_Core_Config::singleton();

// Create upgrader instance and run the menu method
$upgrader = new CRM_UnitLedger_Upgrader();
$reflection = new ReflectionClass($upgrader);
$method = $reflection->getMethod('addUnitLedgerMenu');
$method->setAccessible(true);
$method->invoke($upgrader);

echo "Menu creation completed. Check the logs for details.\n";
?>
