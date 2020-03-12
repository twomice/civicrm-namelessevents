(function(ts) {
  CRM.$(function($) {

    /**
     * Change handler for main 'is enabled' checkbox.
     * @returns {undefined}
     */
    isStudentProgressChange = function isStudentProgressChange() {
      if($('input#is_student_progress').is(':checked')) {
        // If enabled, show matrix.
        $('tr.crm-event-student-progress-form-block-profile_subtypes').show();
      }
      else {
        // Otherwise hide it.
        $('tr.crm-event-student-progress-form-block-profile_subtypes').hide();
      }
    };

    /**
     * Change handler for all 'everyone' checkboxes.
     * @returns {undefined}
     */
    profileAllCheckboxChange = function profileAllCheckboxChange() {
      var el = $(this);
      var mySubtypeCheckboxes = el.closest('tr').find('input[type="checkbox"].namelessevents-studentprogress-profile-subtype');
      if(el.is(':checked')) {
        // If checked, hide the 'real' subtype checkboxes.
        mySubtypeCheckboxes.hide();
      }
      else {
        // Otherwise, show them.
        mySubtypeCheckboxes.show();
      }
    };

    // Assign change handler for main 'is enabled' checkbox.
    $('input#is_student_progress').change(isStudentProgressChange);
    isStudentProgressChange();

    // Assign change handler for 'everyone' checkboxes.
    $('input[type="checkbox"].namelessevents-studentprogress-profile-all').change(profileAllCheckboxChange).change();

  });
}(CRM.ts('namelessevents')));