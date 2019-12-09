<?php

require_once 'raisetheflagforautism.civix.php';
use CRM_Raisetheflagforautism_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function raisetheflagforautism_civicrm_config(&$config) {
  _raisetheflagforautism_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function raisetheflagforautism_civicrm_xmlMenu(&$files) {
  _raisetheflagforautism_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function raisetheflagforautism_civicrm_install() {
  _raisetheflagforautism_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function raisetheflagforautism_civicrm_postInstall() {
  _raisetheflagforautism_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function raisetheflagforautism_civicrm_uninstall() {
  _raisetheflagforautism_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function raisetheflagforautism_civicrm_enable() {
  _raisetheflagforautism_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function raisetheflagforautism_civicrm_disable() {
  _raisetheflagforautism_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function raisetheflagforautism_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _raisetheflagforautism_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function raisetheflagforautism_civicrm_managed(&$entities) {
  _raisetheflagforautism_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function raisetheflagforautism_civicrm_caseTypes(&$caseTypes) {
  _raisetheflagforautism_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function raisetheflagforautism_civicrm_angularModules(&$angularModules) {
  _raisetheflagforautism_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function raisetheflagforautism_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _raisetheflagforautism_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function raisetheflagforautism_civicrm_entityTypes(&$entityTypes) {
  _raisetheflagforautism_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

function raisetheflagforautism_civicrm_preProcess($formName, &$form) {
  if ($formName == "CRM_Core_Form_ShortCode") {
    $form->components['raise_the_flag'] = array(
      'label' => ts("Raise The Flag"),
      'select' => array(
        'key' => 'id',
        'entity' => 'Event',
        'select' => array('minimumInputLength' => 0),
      ),
    );
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function raisetheflagforautism_civicrm_navigationMenu(&$menu) {
  _raisetheflagforautism_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _raisetheflagforautism_civix_navigationMenu($menu);
} // */
