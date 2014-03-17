var es = new EventSource("<!--{route _name='notification'}-->");
es.addEventListener("message", function (event) {
  var div = document.createElement("div");
  var type = event.type;
  var data = JSON.parse(event.data);
  if (data.type == 'notification') {
    notify.createNotification(data.title, { body:data.payload, icon: "img/notification.png" })
  }
});

/* register in browser, if any browser was selected */
$('.preferences .checkbox.browser').on('click', function() {
  notify.requestPermission();
});