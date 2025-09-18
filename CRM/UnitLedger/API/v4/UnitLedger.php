<?php

namespace CRM\UnitLedger\API\v4;

use Civi\Api4\Generic\AbstractEntity;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\BasicCreateAction;
use Civi\Api4\Generic\BasicUpdateAction;
use Civi\Api4\Generic\BasicDeleteAction;
use Civi\Api4\Generic\BasicGetFieldsAction;

/**
 * UnitLedger APIv4 Entity
 * 
 * Provides CRUD operations for the unit ledger system.
 */
class UnitLedger extends AbstractEntity {

  /**
   * @return string
   */
  public static function getEntityName() {
    return 'UnitLedger';
  }

  /**
   * @return string
   */
  public static function getEntityTitle() {
    return 'Unit Ledger';
  }

  /**
   * @return string
   */
  public static function getEntityDescription() {
    return 'Append-only unit ledger for tracking unit transactions';
  }

  /**
   * @return string
   */
  public static function getTableName() {
    return 'civicrm_unit_ledger';
  }

  /**
   * @return string
   */
  public static function getClass() {
    return __CLASS__;
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetAction
   */
  public static function get() {
    return new BasicGetAction(static::getEntityName(), __FUNCTION__, function (BasicGetAction $action) {
      return \CRM_UnitLedger_BAO_UnitLedger::getQuery($action);
    });
  }

  /**
   * @return \Civi\Api4\Generic\BasicCreateAction
   */
  public static function create() {
    return new BasicCreateAction(static::getEntityName(), __FUNCTION__, function (BasicCreateAction $action) {
      return \CRM_UnitLedger_BAO_UnitLedger::createRecord($action);
    });
  }

  /**
   * @return \Civi\Api4\Generic\BasicUpdateAction
   */
  public static function update() {
    return new BasicUpdateAction(static::getEntityName(), __FUNCTION__, function (BasicUpdateAction $action) {
      return \CRM_UnitLedger_BAO_UnitLedger::updateRecord($action);
    });
  }

  /**
   * @return \Civi\Api4\Generic\BasicDeleteAction
   */
  public static function delete() {
    return new BasicDeleteAction(static::getEntityName(), __FUNCTION__, function (BasicDeleteAction $action) {
      return \CRM_UnitLedger_BAO_UnitLedger::deleteRecord($action);
    });
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields() {
    return new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function (BasicGetFieldsAction $action) {
      return \CRM_UnitLedger_BAO_UnitLedger::getFields($action);
    });
  }

  /**
   * Recompute balances for a given case and date range
   * 
   * @return \Civi\Api4\Generic\BasicAction
   */
  public static function recomputeBalances() {
    return new \Civi\Api4\Generic\BasicAction(static::getEntityName(), __FUNCTION__, function ($action) {
      return \CRM_UnitLedger_BAO_UnitLedger::recomputeBalances($action);
    });
  }

  /**
   * Post units for an activity (delta-aware)
   * 
   * @return \Civi\Api4\Generic\BasicAction
   */
  public static function postDelta() {
    return new \Civi\Api4\Generic\BasicAction(static::getEntityName(), __FUNCTION__, function ($action) {
      return \CRM_UnitLedger_BAO_UnitLedger::postDelta($action);
    });
  }

  /**
   * Get balance for a case/program/date
   * 
   * @return \Civi\Api4\Generic\BasicAction
   */
  public static function getBalance() {
    return new \Civi\Api4\Generic\BasicAction(static::getEntityName(), __FUNCTION__, function ($action) {
      return \CRM_UnitLedger_BAO_UnitLedger::getBalance($action);
    });
  }

}
