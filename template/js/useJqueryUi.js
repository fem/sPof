
$(function() {
  $("input.date").datepicker({
    dateFormat: 'dd.mm.yy',
    showWeek: true,
    firstDay: 1,
    prevText: 'voriger Monat',
    nextText: 'nächster Monat',
    monthNames: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
    dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
    dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa']
  });

  $("input.time").timepicker({
    timeFormat: 'H:i',
    currentText: 'Jetzt',
    closeText: 'Fertig',
    timeOnlyTitle: 'Uhrzeit auswählen',
    timeText: 'Zeit',
    hourText: 'Stunde',
    minuteText: 'Minute',
    step: 15
  });
});
