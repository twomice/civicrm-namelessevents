<?php

require_once 'namelessevents.civix.php';
use CRM_Namelessevents_ExtensionUtil as E;

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function namelessevents_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  // FIXME: this code is incomplete.
  return;
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Validating the "online registration" event config form, ensure 'date of birth'
    // and 'contact sub-type' fields are included in at least one profile for the main
    // participant.

    // Don't bother with this if we've disabled online registration.
    if (CRM_Utils_Array::value('is_online_registration', $form->_submitValues)) {
      $requiredUfFieldNames = ['birth_date', 'contact_sub_type'];

      // List of all included profiles:
      $profileIds = [];
      $profileIds[] = CRM_Utils_Array::value('custom_pre_id', $form->_submitValues);
      $profileIds[] = CRM_Utils_Array::value('custom_post_id', $form->_submitValues);
      $profileIds = array_merge($profileIds, CRM_Utils_Array::value('custom_post_id_multiple', $form->_submitValues, []));
      // Strip any empty values and duplicate values.
      $profileIds = array_unique(array_filter($profileIds));

      // Check that the required fields appear in at least one of the selected profiles.
      $requiredUfFieldNames = ['birth_date', 'contact_sub_type'];
      foreach ($requiredUfFieldNames as $requiredUfFieldName) {
        $apiParams = [
          'uf_group_id' => ['IN' => $profileIds],
          'field_name' => $requiredUfFieldName,
          'is_active' => TRUE,
        ];
        $getFields = civicrm_api3('UFField', 'get', $apiParams);
        if (!$getFields['count']) {
          $errors['is_online_registration'] = E::ts('If allowing online registration, you must provide both the "Date of Birth" and "Contact Sub-Type" fields in one of the "Include Profile" settings in the "Registration Screen" section.');
          break;
        }
      }

      // Also ensure those fields are in a profile for 'additional participants',
      // but don't bother with this if we've disabled 'multiple participant' registration.
      if (CRM_Utils_Array::value('is_multiple_registrations', $form->_submitValues)) {
        $additionalProfilesError = FALSE;
        $profileIds = [];
        $profileIds[] = CRM_Utils_Array::value('additional_custom_pre_id', $form->_submitValues);
        $profileIds[] = CRM_Utils_Array::value('additional_custom_post_id', $form->_submitValues);
        $profileIds = array_merge($profileIds, CRM_Utils_Array::value('additional_custom_post_id_multiple', $form->_submitValues, []));
        // Strip any empty values and duplicate values.
        $profileIds = array_unique(array_filter($profileIds, 'is_numeric'));

        if (empty($profileIds)) {
          $additionalProfilesError = TRUE;
        }
        else {
          // Check that the required fields appear in at least one of the selected "additional participant" profiles.
          foreach ($requiredUfFieldNames as $requiredUfFieldName) {
            $apiParams = [
              'uf_group_id' => ['IN' => $profileIds],
              'field_name' => $requiredUfFieldName,
              'is_active' => TRUE,
            ];
            $getFields = civicrm_api3('UFField', 'get', $apiParams);
            if (!$getFields['count']) {
              $additionalProfilesError = TRUE;
              break;
            }
          }
        }
        if ($additionalProfilesError) {
          $errors['is_multiple_registrations'] = E::ts('If allowing multiple participant registration, you must provide both the "Date of Birth" and "Contact Sub-Type" fields in one of the "Profile for Additional Participants" settings in the "Registration Screen" section.');
        }
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
      $eventSettings = CRM_Namelessevents_Settings::getEventSettings($eventId);
      $tabIsValid = (CRM_Utils_Array::value('is_student_progress', $eventSettings));

      $tabs['studentprogress'] = array(
        'title' => E::ts('Student Progress'),
        'link' => NULL, // 'link' is automatically provided if we're under the 'civicrm/event/manage' path.
        'class' => 'ajaxForm', // allows form to re-load itself on save.
        'valid' => $tabIsValid, // indicates whether tab has actively used settings.
        'active' => TRUE,
        'current' => FALSE, // setting this to FALSE prevents the tab from pre-loading
                            // focus when the page is loaded.
      );
    }
    else {
      $tabs['studentprogress'] = array(
        'title' => E::ts('Student Progress'),
        'url' => 'civicrm/event/manage/studentprogress',
        'field' => 'is_student_progress',
      );
    }
  }

  // on manage events listing screen, this section sets studentprogress tab in configuration popup as enabled/disabled.
  if ($tabsetName == 'civicrm/event/manage/rows' && $eventId = CRM_Utils_Array::value('event_id', $context)) {
    $eventSettings = CRM_Namelessevents_Settings::getEventSettings($eventId);
    $tabs[$eventId]['is_student_progress'] = CRM_Utils_Array::value('is_student_progress', $eventSettings);
  }
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
