<?php

/**
 * Utility methods for Namelessevents
 *
 */
class CRM_Namelessevents_Util {

  /**
   * Calculate sub-types based on birthdate, and print json object.
   */
  public static function getSubTypeIdPerBirthDate($birthDate) {
    $util = CRM_Namelessprogress_Util::singleton();
    $contact = [
      'birth_date' => $birthDate,
    ];
    $age = $util->calculateAge($contact);
    $allSubTypeNames = CRM_Ageprogress_Util::calculateAgeprogressSubTypes($age);
    $trueSubTypeNames = array_filter($allSubTypeNames);
    $subTypeName = reset(array_keys($trueSubTypeNames));

    $contactType = \Civi\Api4\ContactType::get()
      ->addWhere('name', '=', $subTypeName)
      ->execute()
      ->first();
    return $contactType['id'];
  }

}
