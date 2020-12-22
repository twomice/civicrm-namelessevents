<?php

require_once 'namelessevents.civix.php';
use CRM_Namelessevents_ExtensionUtil as E;

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function namelessevents_civicrm_buildForm($formName, &$form) {
  if (
    $formName == 'CRM_Event_Form_Registration_Register'
    || $formName == 'CRM_Event_Form_Registration_AdditionalParticipant'
  ) {
    $eventId = $form->getVar('_eventId');
    $eventSettings = CRM_Namelessevents_Settings::getEventSettings($eventId);
    // If Student Progress is enabled for this event:
    if (CRM_Utils_Array::value('is_student_progress', $eventSettings)) {
      // Add a hidden form element, #ageprogressSubType, which will store the
      // age-based subtype ID based on contact's birthdate.
      $form->add(
        // field type
        'hidden',
        // field name
        'ageprogressSubType',
        NULL,
        ['id' => 'ageprogressSubType']
      );
      // Get the contact's birthdate, and fill in the age-based subtype ID,
      // if birthdate is available.
      $defaults = $form->getVar('_defaults');
      if ($birthDate = CRM_Utils_Array::value('birth_date', $defaults)) {
        $form->setDefaults([
          'ageprogressSubType' => CRM_Namelessevents_Util::getSubTypeIdPerBirthDate($birthDate),
        ]);
      }
      // Add a JS script which will help us fetch age-based subtype ID as
      // birthdate is changed on-page:
      CRM_Core_Resources::singleton()->addScriptFile('namelessevents', 'js/CRM_Event_Form_Registration.js');
    }
  }
}

/**
 * Implements hook_civicrm_profcond_alterConfig().
 */
function namelessevents_civicrm_profcond_alterConfig(&$config, $pageType, $entityId) {
  // If we're on an event registration page.
  if ($pageType == 'event') {
    // Get Student Progress settings for this event.
    $eventSettings = CRM_Namelessevents_Settings::getEventSettings($entityId);
    // If Student Progress is enabled for this event:
    if (CRM_Utils_Array::value('is_student_progress', $eventSettings)) {
      // Get all profiles configured for this event.
      $ufJoins = Civi\Api4\UFJoin::get()
        ->addWhere('entity_table', '=', 'civicrm_event')
        ->addWhere('entity_id', '=', $entityId)
        ->addChain('ufGroup', \Civi\Api4\UFGroup::get()->addWhere('id', '=', '$uf_group_id'))
        ->execute();

      // Create one profcond rule for each profile, showing it for enabled Ageprogress SubTypes,
      // and hiding it otherwise.
      foreach ($ufJoins as $ufJoin) {
        $profileId = $ufJoin['uf_group_id'];
        $subTypeIds = CRM_Utils_Array::value($profileId, $eventSettings['profiles'], []);
        if (in_array('all', $subTypeIds)) {
          // This profile is configured to show for everyone, so don't create
          // any show/hide rules for it; just skip it.
          continue;
        }
        $config['event'][$entityId]['namelessevents_profile_' . $profileId] = [
          'conditions' => [
            'all_of' => [
              [
                'id' => 'ageprogressSubType',
                'op' => 'value_is_one_of',
                // Map subtypeIds to strings, because profcond will be comparing
                // to the value of a the hidden #ageprogressSubType element, which
                // in JS will be a string value.
                'value' => array_map('strval', $subTypeIds),
              ],
            ],
          ],
          'states' => [
            'pass' => [
              'profiles' => [
                $profileId => [
                  'display' => 'show',
                ],
              ],
            ],
            'fail' => [
              'profiles' => [
                $profileId => [
                  'display' => 'hide',
                ],
              ],
            ],
          ],
        ];
      }
    }
  }
}

/**
 * Implements hook_civicrm_validateForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_validateForm/
 */
function namelessevents_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Event_Form_ManageEvent_Registration') {
    // Validating the "online registration" event config form, ensure 'date of birth'
    // fields are included in at least one profile for the main participant.

    $studentProgressSettings = CRM_Namelessevents_Settings::getEventSettings($form->getVar('_id'));

    // Only bother with this if Student Progress is enabled for this event.
    if (
      CRM_Utils_Array::value('is_online_registration', $form->_submitValues)
      && CRM_Utils_Array::value('is_student_progress', $studentProgressSettings)
    ) {

      // List of all included profiles:
      $profileIds = [];
      $profileIds[] = CRM_Utils_Array::value('custom_pre_id', $form->_submitValues);
      $profileIds[] = CRM_Utils_Array::value('custom_post_id', $form->_submitValues);
      $profileIds = array_merge($profileIds, CRM_Utils_Array::value('custom_post_id_multiple', $form->_submitValues, []));
      // Strip any empty values and duplicate values.
      $profileIds = array_unique(array_filter($profileIds));

      // Check that the required fields appear in at least one of the selected profiles.
      $requiredUfFieldNames = ['birth_date'];
      foreach ($requiredUfFieldNames as $requiredUfFieldName) {
        $apiParams = [
          'uf_group_id' => ['IN' => $profileIds],
          'field_name' => $requiredUfFieldName,
          'is_active' => TRUE,
        ];
        $getFields = civicrm_api3('UFField', 'get', $apiParams);
        if (!$getFields['count']) {
          $errors['is_online_registration'] = E::ts('If allowing online registration with Student Progress enabled, you must provide the "Date of Birth" field in one of the "Include Profile" settings in the "Registration Screen" section.');
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
          $errors['is_multiple_registrations'] = E::ts('If allowing multiple participant registration with Student Progress enabled, you must provide the "Date of Birth" field in one of the "Profile for Additional Participants" settings in the "Registration Screen" section.');
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

      $tabs['studentprogress'] = [
        'title' => E::ts('Student Progress'),
        // 'link' is automatically provided if we're under the 'civicrm/event/manage' path.
        'link' => NULL,
        // allows form to re-load itself on save.
        'class' => 'ajaxForm',
        // indicates whether tab has actively used settings.
        'valid' => $tabIsValid,
        'active' => TRUE,
        // setting this to FALSE prevents the tab from pre-loading
        // focus when the page is loaded.
        'current' => FALSE,
      ];
    }
    else {
      $tabs['studentprogress'] = [
        'title' => E::ts('Student Progress'),
        'url' => 'civicrm/event/manage/studentprogress',
        'field' => 'is_student_progress',
      ];
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
