(function(ts) {
  CRM.$(function($) {

    isStudentProgressChange = function isStudentProgressChange() {
      if($('input#is_student_progress').is(':checked')) {
        $('tr.crm-event-student-progress-form-block-profile_subtypes').show();
      }
      else {
        $('tr.crm-event-student-progress-form-block-profile_subtypes').hide();
      }      
    }

    $('input#is_student_progress').change(isStudentProgressChange);
    isStudentProgressChange();

  });
}(CRM.ts('namelessevents')));