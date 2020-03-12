<?php
use CRM_Namelessevents_ExtensionUtil as E;

class CRM_Namelessevents_Page_Ajax extends CRM_Core_Page {

  public function run() {
    $method = CRM_Utils_Request::retrieve('method', 'String');
    if (method_exists('CRM_Namelessevents_Util_Ajax', $method)) {
      call_user_func(['CRM_Namelessevents_Util_Ajax', $method]);
    }
    else {
      header('HTTP/1.0 403 Forbidden');
      CRM_Utils_System::civiExit();
    }
  }

}
