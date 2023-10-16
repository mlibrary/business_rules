(function ($, window, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.updateOptionsCommand = function (ajax, response, status) {
    var elementId = response.elementId;
    var options = response.options;
    var element = $("select[id^=" + elementId + "]")[0];
    // Handle id's that changed by AJAX.
    if (element === undefined) {
      element = document.querySelector('[data-drupal-selector="' + elementId + '"]');
    }
    // Perform update only for valid element.
    if (element !== undefined) {
      // Select list.
      if (element.tagName === 'SELECT') {
        // Save the current selection so it can be reapplied later.
        var optionsArray = Array.prototype.slice.call(element.options);
        var currentSelection = optionsArray
          .filter(option => option.selected)
          .map(option => option.value);
        // Remove the current options
        element.options.length = 0;
        for (var i = 0; i <= options.length; i++) {
          if (options.hasOwnProperty(i)) {
            element.options.add(new Option(
              options[i].value,
              options[i].key,
              false,
              currentSelection.includes(options[i].key.toString())
            ));
          }
        }
        if (response.multiple) {
          element.setAttribute('multiple', 'multiple');
        }
        element.dispatchEvent(new Event('change'));
      }
      else if (element.tagName === 'FIELDSET') {
        element = document.getElementById(elementId);
        // Checkbox list.
        if (element.className === 'form-checkboxes') {
          var fieldNameBase = elementId.substr(5);
          // Save the current selection so it can be reapplied later.
          var optionsArray = Array.prototype.slice.call(element.querySelectorAll('.form-checkbox'));
          var currentSelection = optionsArray
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
          // Remove the current options
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          for (var i = 0; i <= options.length; i++) {
            // Skip '_none' option
            // This option cannot be removed on the server side or the sorting is removed
            if (i === 0) {
              continue;
            }
            if (options.hasOwnProperty(i)) {
              var fieldName = fieldNameBase + '-' + options[i].key;
              var div = document.createElement('div');
              div.setAttribute('class', 'js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-' + fieldName + ' form-item-' + fieldName);
              var input = document.createElement('input');
              input.setAttribute('data-drupal-selector', elementId + '-' + options[i].key);
              input.setAttribute('type', 'checkbox');
              input.setAttribute('id', elementId + '-' + options[i].key);
              input.setAttribute('name', fieldNameBase.replace(/[-]/g, '_') + '[' + options[i].key + ']');
              input.setAttribute('value', options[i].key);
              input.setAttribute('class', 'form-checkbox');
              if (currentSelection.includes(options[i].key.toString())) {
                input.setAttribute('checked', 'checked');
              }
              var label = document.createElement('label');
              label.setAttribute('for', elementId + '-' + options[i].key);
              label.setAttribute('class', 'option');
              label.appendChild(document.createTextNode(options[i].value));
              div.appendChild(input);
              div.appendChild(document.createTextNode (' '));
              div.appendChild(label);
              element.appendChild(div);
            }
          }
          element.dispatchEvent(new Event('change'));
        }
        // Radio buttons list.
        else if (element.className === 'form-radios') {
          var fieldNameBase = elementId.substr(5);
          // Save the current selection so it can be reapplied later.
          var optionsArray = Array.prototype.slice.call(element.querySelectorAll('.form-radio'));
          var currentSelection = optionsArray
            .filter(radioButton => radioButton.checked)
            .map(radioButton => radioButton.value);
          // Remove the current options
          while (element.firstChild) {
            element.removeChild(element.firstChild);
          }
          for (var i = 0; i <= options.length; i++) {
            // Skip '_none' option
            // This option cannot be removed on the server side or the sorting is removed
            if (i === 0) {
              continue;
            }
            if (options.hasOwnProperty(i)) {
              var div = document.createElement('div');
              div.setAttribute('class', 'js-form-item form-item js-form-type-radio form-type-radio js-form-item-' + fieldNameBase + ' form-item-' + fieldNameBase);
              var input = document.createElement('input');
              input.setAttribute('data-drupal-selector', elementId + '-' + options[i].key);
              input.setAttribute('type', 'radio');
              input.setAttribute('id', elementId + '-' + options[i].key);
              input.setAttribute('name', fieldNameBase.replace(/[-]/g, '_'));
              input.setAttribute('value', options[i].key);
              input.setAttribute('class', 'form-radio');
              if (currentSelection.includes(options[i].key.toString())) {
                input.setAttribute('checked', 'checked');
              }
              var label = document.createElement('label');
              label.setAttribute('for', elementId + '-' + options[i].key);
              label.setAttribute('class', 'option');
              label.appendChild(document.createTextNode(options[i].value));
              div.appendChild(input);
              div.appendChild(document.createTextNode(' '));
              div.appendChild(label);
              element.appendChild(div);
            }
          }
          element.dispatchEvent(new Event('change'));
        }
      }
    }
  };

})(jQuery, window, Drupal, drupalSettings);
