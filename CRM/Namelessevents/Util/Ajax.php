<?php

/**
 * Ajax methods to be called from CRM_Namelessevents_Page_Ajax
 *
 */
class CRM_Namelessevents_Util_Ajax {

  /**
   * Calculate sub-types based on birthdate, and print json object.
   */
  public static function getSubTypePerBirthDate() {
    $util = CRM_Namelessprogress_Util::singleton();
    $contact = [
      'birth_date' => CRM_Utils_Request::retrieve('birth_date', 'String'),
    ];
    $age = $util->calculateAge($contact);
    $subTypes = CRM_Ageprogress_Util::calculateAgeprogressSubTypes($age);
    CRM_Utils_JSON::output(['age' => $age, 'subTypes' => $subTypes]);
  }

}
