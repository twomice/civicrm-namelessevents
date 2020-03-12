<?php

use CRM_Namelessevents_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Namelessevents_Form_Event_ConfigSubtypeProfiles extends CRM_Event_Form_ManageEvent {

  /**
   * The ID of the entity (in this case, the event) which we're configuring.
   *
   * @var int
   * @see getEntityId()
   */
  private $entityId = NULL;

  /**
   * Returns the ID of the entity (in this case, the event) which we're configuring.
   *
   * @return int
   */
  protected function getEntityId() {
    if ($this->entityId === NULL) {
      $this->entityId = !empty($this->_id) ? $this->_id : NULL;
      if ($this->entityId === NULL) {
        $this->entityId = CRM_Utils_Request::retrieve('event_id', 'Positive', $this, TRUE);
      }
    }
    return $this->entityId;
  }

  public function buildQuickForm() {
    $this->assign('selectedChild', 'studentprogress');
    $this->add(
      'hidden', // field type
      'event_id' // field name
    );
    $this->addElement(
      'checkbox',
      'is_student_progress',
      E::ts('Student Progress')
    );

    $profiles = $subTypes = [];

    $ageprogressSubTypes = Civi\Api4\AgeprogressContactType::get()
      ->addWhere('is_ageprogress', '=', 1)
      ->addChain('contactType', \Civi\Api4\ContactType::get()->addWhere('id', '=', '$contact_type_id'))
      ->execute();
    foreach ($ageprogressSubTypes as $ageprogressSubType) {
      $subTypes[$ageprogressSubType['contact_type_id']] = $ageprogressSubType['contactType'][0]['label'];
    }
    $ufJoins = Civi\Api4\UFJoin::get()
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $this->getEntityId())
      ->addChain('ufGroup', \Civi\Api4\UFGroup::get()->addWhere('id', '=', '$uf_group_id'))
      ->execute();
    $hasBirthDateMarker = '<i class="crm-i fa-calendar" style="margin-left: 1em"></i> ';
    foreach ($ufJoins as $ufJoin) {
      // Determine if this profile has Birth Date field, so we can inform the user.
      // There's no API4 for UFField, so use api3
      $apiParams = [
        'uf_group_id' => $ufJoin['uf_group_id'],
        'field_name' => 'birth_date',
        'is_active' => TRUE,
      ];
      $fieldCount = civicrm_api3('UFField', 'getcount', $apiParams);
      $profiles[$ufJoin['uf_group_id']] = $ufJoin['ufGroup'][0]['title'] . ($fieldCount ? $hasBirthDateMarker : "");
    }

    $this->assign('profiles', $profiles);
    $this->assign('subTypes', $subTypes);
    $this->assign('hasBirthDateMarker', $hasBirthDateMarker);

    foreach ($profiles as $profileId => $profileTitle) {
      foreach ($subTypes as $subTypeId => $subTypeLabel) {
        $this->addElement('checkbox', "profile[{$profileId}][{$subTypeId}]", '', NULL, ['title' => "$profileTitle / $subTypeLabel"]);
      }
    }

    CRM_Core_Resources::singleton()->addScriptFile('namelessevents', 'js/CRM_Namelessevents_Form_Event_ConfigSubtypeProfiles.js');
    // export form elements
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $settings = [
      'is_student_progress' => FALSE,
      'profiles' => [],
    ];

    if (CRM_Utils_Array::value('is_student_progress', $values)) {
      $settings['is_student_progress'] = TRUE;
      foreach (CRM_Utils_Array::value('profile', $values, []) as $profileId => $subTypes) {
        $settings['profiles'][$profileId] = array_keys($subTypes);
      }
    }
    CRM_Namelessevents_Settings::saveAllEventSettings($this->getEntityId(), $settings);

    parent::postProcess();
  }

  public function setDefaultValues() {
    $defaults = [
      'event_id' => $this->getEntityId(),
    ];
    $eventSettings = CRM_Namelessevents_Settings::getEventSettings($this->getEntityId());
    $defaults['is_student_progress'] = $eventSettings['is_student_progress'];
    foreach ($eventSettings['profiles'] as $profileId => $subTypeIds) {
      foreach ($subTypeIds as $subTypeId) {
        $defaults["profile[{$profileId}][{$subTypeId}]"] = TRUE;
      }
    }
    return $defaults;
  }

}
