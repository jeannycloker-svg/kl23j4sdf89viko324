// Codes just run once the DOM has loaded.
// @See https://www.drupal.org/docs/8/api/javascript-api/javascript-api-overview
(function($) {
  const editorID = "json-editor";
  const valueID = "config-value";
  var container = document.getElementById(editorID);
  var data = {};
  var options = {
      mode: "tree",
      modes: ['code', 'tree'], // allowed modes
      name: "Configuration",
  };
  
  // json hidden element
  var jsonInput = document.getElementById(valueID);
  //json data
  if (jsonInput && jsonInput.value) {
    data = JSON.parse(jsonInput.value);
  }
  
  var editor = new JSONEditor(container, options, data);
  
  editor.setName('Configuration');
  
  //The input form.
  var form = jsonInput.form;
  
  var submit = function(e) {
    jsonInput.value = JSON.stringify(editor.get());
  };
  
  // Form submit event.
  if(form.addEventListener){
    form.addEventListener("submit", submit);  //Modern browsers
  }else if(ele.attachEvent){
    form.attachEvent('onsubmit', submit);            //Old IE
  }
  
})(jQuery);