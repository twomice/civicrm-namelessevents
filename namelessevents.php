<?php

require_once 'namelessevents.civix.php';
use CRM_Namelessevents_ExtensionUtil as E;

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function namelessevents_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Validating the "online registration" event config form, ensure 'date of birth'
    // and 'contact sub-type' fields are included in at least one profile.

    // List of all included profiles:
    $profileIds = [];
    $profileIds[] = CRM_Utils_Array::value('custom_pre_id', $form->_submitValues);
    $profileIds[] = CRM_Utils_Array::value('custom_post_id', $form->_submitValues);
    $profileIds[] = CRM_Utils_Array::value('additional_custom_pre_id', $form->_submitValues);
    $profileIds[] = CRM_Utils_Array::value('additional_custom_post_id', $form->_submitValues);
    $profileIds = array_merge($profileIds, CRM_Utils_Array::value('custom_post_id_multiple', $form->_submitValues, []));
    $profileIds = array_merge($profileIds, CRM_Utils_Array::value('additional_custom_post_id_multiple', $form->_submitValues, []));

    // Check that the required fields appear in at least one of the selected profiles.
    $requiredUfFieldNames = ['birth_date', 'contact_sub_type'];
    foreach ($requiredUfFieldNames as $requiredUfFieldName) {
      $apiParams = [
        'uf_group_id' => ['IN' => $profileIds],
        'field_name' => $requiredUfFieldName,
        'is_active' => TRUE,
      ];
      $fields = civicrm_api3('UFField', 'get', $apiParams);
      if (!$fields['count']) {
        $errors['is_online_registration'] = E::ts('If allowing online registration, you must provide both the "Date of Birth" and "Contact Sub-Type" fields in one of the profiles in the "Registration Screen" section.') . " not found: $requiredUfFieldName";
      }
    }
  }
}

/**
 * Implements hook_civicrm_tabset().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tabset/
 */
function namelessevents_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName == 'civicrm/event/manage') {

    if ($eventId = CRM_Utils_Array::value('event_id', $context)) {
//      $eventSettings = CRM_Participantletter_Settings::getEventSettings($eventId);
      $tabs['namelesseventssubtypeprofiles'] = array(
        'title' => E::ts('Profiles/Sub-types'),
        'link' => NULL, // 'link' is automatically provided if we're under the 'civicrm/event/manage' path.
        'class' => 'ajaxForm', // allows form to re-load itself on save.
        'valid' => TRUE, //(bool)CRM_Utils_Array::value('is_participantletter', $eventSettings),
        'active' => TRUE,
        'current' => TRUE,  // setting this to FALSE prevents the tab from getting
                            // focus when called directly, e.g., from under the
                            // "Configure" link on the Manage Events listing page.
      );
    }
    else {
      $tabs['namelesseventssubtypeprofiles'] = array(
        'title' => E::ts('Profiles/Sub-types'),
        'url' => 'civicrm/event/manage/namelessevents/configsubtypeprofiles',
        'field' => 'is_register_online',
      );
    }
  }

  // on manage events listing screen, this section sets particpantletter tab in configuration popup as enabled/disabled.
//  if ($tabsetName == 'civicrm/event/manage/rows' && $eventId = CRM_Utils_Array::value('event_id', $context)) {
//    $eventSettings = CRM_Participantletter_Settings::getEventSettings($eventId);
//    $tabs[$eventId]['is_participantletter'] = CRM_Utils_Array::value('is_participantletter', $eventSettings);
//  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/ 
 */
function namelessevents_civicrm_config(&$config) {
  _namelessevents_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function namelessevents_civicrm_xmlMenu(&$files) {
  _namelessevents_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function namelessevents_civicrm_install() {
  _namelessevents_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function namelessevents_civicrm_postInstall() {
  _namelessevents_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function namelessevents_civicrm_uninstall() {
  _namelessevents_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function namelessevents_civicrm_enable() {
  _namelessevents_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function namelessevents_civicrm_disable() {
  _namelessevents_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function namelessevents_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _namelessevents_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function namelessevents_civicrm_managed(&$entities) {
  _namelessevents_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function namelessevents_civicrm_caseTypes(&$caseTypes) {
  _namelessevents_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function namelessevents_civicrm_angularModules(&$angularModules) {
  _namelessevents_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function namelessevents_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _namelessevents_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function namelessevents_civicrm_entityTypes(&$entityTypes) {
  _namelessevents_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function namelessevents_civicrm_themes(&$themes) {
  _namelessevents_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
function namelessevents_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function namelessevents_civicrm_navigationMenu(&$menu) {
  _namelessevents_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _namelessevents_civix_navigationMenu($menu);
} // */
