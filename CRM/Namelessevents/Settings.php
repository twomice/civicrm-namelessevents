<?php

/**
 * Settings-related utility methods.
 *
 */
class CRM_Namelessevents_Settings {

  public static function getEventSettings($eventId) {
    if (!isset(Civi::$statics[__CLASS__]['eventSettings'][$eventId])) {
      Civi::$statics[__CLASS__]['eventSettings'][$eventId] = [
        'is_student_progress' => FALSE,
        'profiles' => [],
      ];
      $namelesseventsProfileGet = \Civi\Api4\NamelesseventsProfiles::get()
        ->addWhere('event_id', '=', $eventId)
        ->execute()
        ->first();

      if (!empty($namelesseventsProfileGet)) {
        Civi::$statics[__CLASS__]['eventSettings'][$eventId]['is_student_progress'] = TRUE;
        Civi::$statics[__CLASS__]['eventSettings'][$eventId]['profiles'] = \GuzzleHttp\json_decode($namelesseventsProfileGet['settings'], TRUE);
      }
    }
    return Civi::$statics[__CLASS__]['eventSettings'][$eventId];
  }

  public static function saveAllEventSettings($eventId, $settings) {
    if (CRM_Utils_Array::value('is_student_progress', $settings)) {
      $profilesJson = GuzzleHttp\json_encode($settings['profiles']);
      $namelesseventsProfileGet = \Civi\Api4\NamelesseventsProfiles::get()
        ->addWhere('event_id', '=', $eventId)
        ->execute()
        ->first();
      if (empty($namelesseventsProfileGet)) {
        $namelesseventsProfile = \Civi\Api4\NamelesseventsProfiles::create()
          ->addValue('event_id', $eventId);
      }
      else {
        $namelesseventsProfile = \Civi\Api4\NamelesseventsProfiles::update()
          ->addWhere('id', '=', $namelesseventsProfileGet['id']);
      }
      // Whether create or update, add the jsonified settings.
      $namelesseventsProfile->addValue('settings', $profilesJson);
    }
    else {
      $namelesseventsProfile = \Civi\Api4\NamelesseventsProfiles::delete()
        ->addWhere('event_id', '=', $eventId);
    }

    try {
      $namelesseventsProfile->execute();
      return TRUE;
    }
    catch (API_Exception $e) {
      CRM_Core_Error::debug_log_message('Namelessevents: encountered error in NamelesseventsProfiles API while updating settings for event ID=' . $eventId . '; API error message: ' . $e->getMessage());
      CRM_Core_Error::debug_var('Namelessevents: NamelesseventsProfiles settings', $settings);
      return FALSE;
    }
  }

}
