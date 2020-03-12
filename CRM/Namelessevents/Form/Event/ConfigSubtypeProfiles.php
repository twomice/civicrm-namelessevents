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
    foreach ($ufJoins as $ufJoin) {
      $profiles[$ufJoin['uf_group_id']] = $ufJoin['ufGroup'][0]['title'];
    }

    $this->assign('profiles', $profiles);
    $this->assign('subTypes', $subTypes);

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

    if ($isStudentProgress = CRM_Utils_Array::value('is_student_progress', $values)) {
      $settings = [];
      foreach (CRM_Utils_Array::value('profile', $values, []) as $profileId => $subTypes) {
        $settings[$profileId] = array_keys($subTypes);
      }
      $namelesseventsProfileGet = \Civi\Api4\NamelesseventsProfiles::get()
        ->addWhere('event_id', '=', $this->getEntityId())
        ->execute()
        ->first();
      if (empty($namelesseventsProfileGet)) {
        $namelesseventsProfile = \Civi\Api4\NamelesseventsProfiles::create()
          ->addValue('event_id', $this->getEntityId());
      }
      else {
        $namelesseventsProfile = \Civi\Api4\NamelesseventsProfiles::update()
          ->addWhere('id', '=', $namelesseventsProfileGet['id']);
      }
      // Whether create or update, add the jsonified settings.
      $json = json_encode($settings);
      $namelesseventsProfile->addValue('settings', $json);
      $namelesseventsProfile->execute();
    }
    else {
      $namelesseventsProfileGet = \Civi\Api4\NamelesseventsProfiles::delete()
        ->addWhere('event_id', '=', $this->getEntityId())
        ->execute();
    }

    parent::postProcess();
  }

  public function setDefaultValues() {
    $defaults = [
      'event_id' => $this->getEntityId(),
    ];
    $namelesseventsProfileGet = \Civi\Api4\NamelesseventsProfiles::get()
      ->addWhere('event_id', '=', $this->getEntityId())
      ->execute()
      ->first();
    if (!empty($namelesseventsProfileGet)) {
      $defaults['is_student_progress'] = TRUE;
      $profiles = \GuzzleHttp\json_decode($namelesseventsProfileGet['settings']);
      foreach ($profiles as $profileId => $subTypeIds) {
        foreach ($subTypeIds as $subTypeId) {
          $defaults["profile[{$profileId}][{$subTypeId}]"] = TRUE;
        }
      }
    }
    return $defaults;
  }

}
