(function(ts) {
  CRM.$(function($) {

    /**
     * Change handler for birth date field.
     */
    var birthDateChange = function birthDateChange() {
      var dob = $('input#birth_date').val();
      if (dob.length) {
        // If there's a birth date value, fetch the age-appropriate contact subtype ID
        var jqxhr = $.get( CRM.url('civicrm/namelessevents/ajax', {'birth_date': dob, 'method': 'getSubTypePerBirthDate'}))
          .done(function(data, textStatus, jqXHR) {
            toggleProfileDisplay(data.subtype);
          });
      }     
      else {
        toggleProfileDisplay('');
      }
    };
    
    var toggleProfileDisplay = function toggleProfileDisplay(subtype) {
      $('#ageprogressSubType').val(subtype).change();
    };
    
    // Assign change handler for birth date field.
    $('input#birth_date').change(birthDateChange);
    birthDateChange();

  });
}(CRM.ts('namelessevents')));