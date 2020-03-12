(function(ts) {
  CRM.$(function($) {

    var birthDateChange = function birthDateChange() {
      var dob = $('input#birth_date').val();
      if (dob.length) {
        var jqxhr = $.get( CRM.url('civicrm/namelessevents/ajax', {'birth_date': dob, 'method': 'getSubTypePerBirthDate'}))
          .done(function(data, textStatus, jqXHR) {
            console.log("success", data);
          });
      }     
      else {
        toggleProfileDisplay();
      }
    };
    
    var toggleProfileDisplay = function toggleProfileDisplay(subType) {
      console.log('subtype', subType);
    };
    
    $('input#birth_date').change(birthDateChange);
    birthDateChange();

  });
}(CRM.ts('namelessevents')));