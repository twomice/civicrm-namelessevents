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
    $subTypeId = CRM_Namelessevents_Util::getSubTypeIdPerBirthDate(CRM_Utils_Request::retrieve('birth_date', 'String'));
    $subType = \Civi\Api4\ContactType::get()
      ->addWhere('name', '=', $subTypeId)
      ->execute()
      ->first();
    CRM_Utils_JSON::output(['subtype' => $subTypeId]);
  }

}
