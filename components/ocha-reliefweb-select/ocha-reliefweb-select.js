/* global SimpleAutocomplete */

/**
 * Autocomplete widget handling.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.ochaReliefWebSelect = {
    attach: function (context, settings) {
      // Check support.
      if (!document.addEventListener) {
        return;
      }

      // Translations.
      var t = Drupal.t;

      // Trigger an event on an element.
      function triggerEvent(element, eventName) {
        element.dispatchEvent(new Event(eventName));
      }

      /**
       * Autocomplete selection.
       */

      // Find the closest selection item to insert a new item before.
      function findClosestItem(selection, index) {
        var items = selection.childNodes;
        for (var i = 0, l = items.length; i < l; i++) {
          var item = items[i];
          if (item.hasAttribute && item.getAttribute('data-index') > index) {
            return item;
          }
        }
        return null;
      }
      // Find the selection item matching the given value or return false.
      function findSelectedItem(selection, value) {
        var items = selection.childNodes;
        for (var i = 0, l = items.length; i < l; i++) {
          var item = items[i];
          if (item.hasAttribute && item.getAttribute('data-value') == value) {
            return item;
          }
        }
        return false;
      }
      // Create an item and add it to the selection.
      function createSelectedItem(selection, option) {
        var removeButton = document.createElement('button');
        removeButton.appendChild(document.createTextNode(t('Remove')));
        removeButton.setAttribute('type', 'button');
        removeButton.classList.add('remove');

        var label = document.createElement('span');
        label.classList.add('label');
        label.appendChild(document.createTextNode(option.text));

        var item = document.createElement('div');
        item.appendChild(label);
        item.appendChild(removeButton);
        item.setAttribute('data-value', option.value);
        item.setAttribute('data-index', option.index);

        // Some options like some sources, have a message to display in addition
        // to the value and label.
        if (option.hasAttribute('data-message') && option.getAttribute('data-message') !== '') {
          var message = document.createElement('div');
          message.innerHTML = option.getAttribute('data-message');
          message.classList.add('message');
          item.appendChild(message);
        }

        // Copy the data attributes to the selection item.
        var attributes = option.attributes;
        for (var i = 0, l = attributes.length; i < l; i++) {
          var attribute = attributes[i];
          if (attribute.name.indexOf('data-') === 0 && attribute.name !== 'data-message') {
            item.setAttribute(attribute.name, attribute.value);
          }
        }

        selection.insertBefore(item, findClosestItem(selection, option.index));
      }
      // Remove an item from the selection.
      function deleteSelectedItem(selection, item) {
        if (item.parentNode === selection) {
          selection.removeChild(item);
        }
      }
      // Add an item to the selection if not present.
      function addToSelection(selection, option) {
        if (findSelectedItem(selection, option.value) === false) {
          createSelectedItem(selection, option);
        }
      }
      // Remove an item from a selection if present.
      function removeFromSelection(selection, value) {
        var item = findSelectedItem(selection, value);
        if (item !== false) {
          deleteSelectedItem(selection, item);
        }
      }
      // Update the selection to ensure it is in sync with the selected options.
      function updateSelection(selection, select) {
        if (!selection || !select) {
          return;
        }

        // Remove all the items from the selection.
        while (selection.lastChild) {
          selection.removeChild(selection.lastChild);
        }

        // Create items in the selection for the selected options.
        var options = select.getElementsByTagName('option');
        for (var i = 0, l = options.length; i < l; i++) {
          var option = options[i];
          if (option.selected && option.value !== '_none' && !option.disabled) {
            createSelectedItem(selection, option);
          }
        }

      }
      // Find an option matching the given value.
      function findOption(select, value) {
        var options = select.getElementsByTagName('option');
        for (var i = 0, l = options.length; i < l; i++) {
          var option = options[i];
          if (option.value == value) {
            return option;
          }
        }
        return false;
      }
      // Select an option of a select element if not selected
      function selectOption(select, option) {
        if (!option.selected) {
          option.selected = true;
          // Propagate the modification.
          triggerEvent(select, 'change');
        }
      }
      // Unselect an option from a select element if selected
      function unselectOption(select, option) {
        if (option.selected) {
          option.selected = false;
          // Propagate the modification.
          triggerEvent(select, 'change');
        }
      }

      /**
       * Logic.
       */

      // Check if a character is alpha numeric.
      function isAlphaNumeric(code) {
        // Numeric (0-9), upper alpha (A-Z) or lower alpha (a-z).
        return (code > 47 && code < 58) || (code > 64 && code < 91) || (code > 96 && code < 123);
      }

      // Check a string against a list of terms (as regexp).
      function matchTerms(string, matchers) {
        for (var i = 0, l = matchers.length; i < l; i++) {
          if (string.match(matchers[i]) === null) {
            return false;
          }
        }
        return true;
      }

      // Calculate score based on position of the query terms.
      function calculateScore(string, query, terms) {
        if (string === query) {
          return 11 * terms.length;
        }

        var score = 0;
        for (var i = 0, l = terms.length; i < l; i++) {
          var term = terms[i];
          var start = string.indexOf(term);
          var after = string.charCodeAt(start + term.length);

          if (start === 0) {
            // Contains individual term.
            if (isNaN(after) || !isAlphaNumeric(after)) {
              score += 10;
            }
            // Contains word starting with term.
            else {
              score += 6;
            }
          }
          else {
            var before = string.charCodeAt(start - 1);
            // Contains word starting with term.
            if (!isAlphaNumeric(before)) {
              // Contains word ending with term or word with term inside.
              score += isNaN(after) || !isAlphaNumeric(after) ? 9 : 5;
            }
            else {
              // Contains word ending with term or word with term inside.
              score += isNaN(after) || !isAlphaNumeric(after) ? 3 : 1;
            }
          }
        }
        return score;
      }

      // Extend the base autocomplete with a matching function that ranks
      // the suggestions based on the position of the search terms and the
      // suggestion length.
      var Autocomplete = SimpleAutocomplete.Autocomplete.extend({
        // Find the suggestions matching the query terms from the source.
        match: function (query, source) {
          var removeDiacritics = SimpleAutocomplete.removeDiacritics;
          var escapeRegExp = SimpleAutocomplete.escapeRegExp;
          var trim = SimpleAutocomplete.trim;

          query = removeDiacritics(trim(query));

          var terms = escapeRegExp(query).split(/\s+/);
          var limit = this.options.limit < source.length ? this.options.limit : source.length;
          var matchers = [];
          var data = [];

          for (var i = 0, l = terms.length; i < l; i++) {
            matchers.push(new RegExp(terms[i]));
          }

          // Find the matching suggestions.
          for (var i = 0, l = source.length; i < l; i++) {
            var item = source[i];
            var string = '';
            if (typeof item === 'string') {
              string = removeDiacritics(trim(item));
            }
            else if (item.value) {
              string = removeDiacritics(trim(item.value));
            }
            if (string !== '' && matchTerms(string, matchers)) {
              data.push([calculateScore(string, query, terms), string.length, item, string]);
            }
          }

          // Sort by score. In case of identical score, compare the length.
          data.sort(function (a, b) {
            var scoreA = a[0];
            var scoreB = b[0];
            if (scoreA === scoreB) {
              // Shorter suggestion ranks higher.
              return a[1] - b[1];
            }
            return scoreB - scoreA;
          });

          // Extract the suggestions.
          var results = [];
          for (var i = 0, l = data.length; i < l; i++) {
            results.push(data[i][2]);
          }

          return results.slice(0, limit);
        }
      });

      /**
       * Add an autocomplete widget to a form element.
       */
      function enableAutocomplete(element) {
        var parent = element;
        if (element.nodeName !== 'SELECT') {
          element = element.querySelector('select');
        }

        // Skip if we couldn't find the select element.
        if (!element) {
          return;
        }

        var multiple = element.hasAttribute('multiple');
        var id = element.id;

        // We will add the id to the input so that error links or clicking the
        // label will focus the input element. We change the id on the element
        // so we can still target it easily.
        element.setAttribute('id', id + '--element');

        // Mark the select as being processed for autocomplete.
        element.classList.add('ocha-reliefweb-autocomplete-select');
        element.classList.add('ocha-reliefweb-autocomplete-select--processed');

        // Move the autocomplete path attribute if defined to the select element
        // for consistency.
        if (parent.hasAttribute('data-autocomplete-path') && parent !== element) {
          element.setAttribute('data-autocomplete-path', parent.getAttribute('data-autocomplete-path'));
          parent.removeAttribute('data-autocomplete-path');
        }

        // Prepare the autocomplete input field.
        var input = document.createElement('input');
        input.setAttribute('type', 'search');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('placeholder', t('type and select...'));
        input.setAttribute('id', id);
        input.classList.add('form-element');
        input.classList.add('ocha-reliefweb-autocomplete-input');

        // Toggler button to display all the options.
        var button = document.createElement('button');
        button.appendChild(document.createTextNode(t('Show all options')));
        button.setAttribute('type', 'button');
        button.setAttribute('tabindex', '-1');
        button.classList.add('button');
        button.classList.add('ocha-reliefweb-autocomplete-show-all');

        // Prepare the selection container.
        var selection = document.createElement('div');
        selection.setAttribute('data-selection', '');
        selection.setAttribute('id', id + '--selection');
        selection.classList.add('ocha-reliefweb-selection');

        // Wrapper for the autocomplete components.
        var container = document.createElement('div');
        container.setAttribute('data-autocomplete', '');
        container.setAttribute('role', 'combobox');
        container.setAttribute('aria-expanded', 'false');
        container.setAttribute('aria-haspopup', 'listbox');
        container.classList.add('ocha-reliefweb-autocomplete');
        container.classList.add('ocha-reliefweb-autocomplete--with-show-all');
        container.appendChild(input);
        container.appendChild(button);

        // Add the container and the selection after the select element.
        element.parentNode.insertBefore(container, element.nextSibling);
        element.parentNode.insertBefore(selection, container.nextSibling);

        // For the source we use a function to generate the list of potential
        // matches from the list of options.
        var source = function () {
          var data = [];
          var options = element.getElementsByTagName('option');
          for (var i = 0, l = options.length; i < l; i++) {
            var option = options[i];
            if (option.value !== '_none' && !option.disabled) {
              var text = option.text;
              var shortname = option.getAttribute('data-shortname');
              if (shortname && shortname !== text) {
                text += ' (' + shortname + ')';
              }
              data.push({
                value: text,
                option: option
              });
            }
          }
          return data;
        };

        // Function to create an autocomplete widget.
        var createAutocomplete = function (element, source, options) {
          return new Autocomplete(element, source, options);
        };

        // For normal cases, the source (select options) don't change
        // so we can improve the performances by computing the source
        // already.
        source = source();

        // Set the intitial selection.
        updateSelection(selection, element);

        // Add the autocomplete widget.
        var autocomplete = createAutocomplete(input, source, {
          limit: 50,
          // No delay as the source is static.
          delay: 0,
          // No need to cache all the queries.
          disableCache: true,
          // Class namespace.
          namespace: 'ocha-reliefweb-autocomplete',
          // Prepare the source.
          prepare: function (query, source) {
            var data = typeof source === 'function' ? source() : source;
            return query !== '' ? this.match(query, data) : data;
          },
          // Update the select element and the selection.
          select: function (suggestion) {
            if (autocomplete.selectorIsOpen()) {
              // If only 1 value is acceptable, unselect the other options.
              if (!multiple) {
                var suggestions = typeof this.source === 'function' ? this.source() : this.source;
                for (var i = 0, l = suggestions.length; i < l; i++) {
                  var option = suggestions[i].option;
                  removeFromSelection(selection, option.value);
                  unselectOption(element, option);
                }
              }
              addToSelection(selection, suggestion.option);
              selectOption(element, suggestion.option);
            }
            // Clear the input field.
            autocomplete.clear();
          },
          // Called when a suggestion is rendered in the selector.
          render: function (query, suggestion) {
            var label = 'undefined';
            if (typeof suggestion === 'string') {
              label = suggestion;
            }
            else if (suggestion.label) {
              label = suggestion.label;
            }
            else if (suggestion.value) {
              label = suggestion.value;
            }
            var content = this.highlight(query, label);
            // Add the status attribute to enable custom styling.
            if (suggestion.option && suggestion.option.hasAttribute('data-moderation-status')) {
              var status = suggestion.option.getAttribute('data-moderation-status');
              content = '<div data-moderation-status=' + status + '>' + content + '</div>';
            }
            return content;
          }
        })
        // Keep track of the selector open state.
        .on('opened', function (event) {
          container.setAttribute('aria-expanded', 'true');
        })
        .on('closed', function (event) {
          container.setAttribute('aria-expanded', 'false');
        });

        // Update the container with the id of the autocomplete selector
        // for accessibility.
        container.setAttribute('aria-owns', autocomplete.getSelector().id);

        // Add to or remove from the selection when an option is clicked.
        element.addEventListener('click', function (event) {
          if (event.target && event.target.nodeName === 'OPTION') {
            updateSelection(selection, element);
          }
        });
        // Unselect an option when an item is removed from the selection.
        selection.addEventListener('click', function (event) {
          if (event.target && event.target.nodeName === 'BUTTON') {
            var item = event.target.parentNode;
            // Unselect the corresponding option if any.
            var option = findOption(element, item.getAttribute('data-value'));
            if (option !== false) {
              unselectOption(element, option);
            }
            // Remove the selection item.
            deleteSelectedItem(selection, item);
          }
        });
        // Prevent the selector from closing when changing the focus element
        // after clicking the "show all" button.
        button.addEventListener('mousedown', function (event) {
          autocomplete.preventBlur = true;
          event.preventDefault();
        });
        button.addEventListener('mouseout', function (event) {
          autocomplete.preventBlur = false;
          autocomplete.focus();
          event.preventDefault();
        });
        // Show the full list of selectable options.
        button.addEventListener('click', function (event) {
          if (!autocomplete.selectorIsOpen()) {
            var query = '';
            var data = autocomplete.options.prepare(query, autocomplete.source);
            var limit = autocomplete.options.limit;
            var cacheKey = autocomplete.options.cacheKey(query);
            // Clear the input field.
            autocomplete.clear();
            // Clear the cache.
            autocomplete.cache = {};
            // Increase the limit to make sure we can display all the terms.
            autocomplete.options.limit = data.length;
            // Load all the data into the cache and display the selector.
            autocomplete.handleData(query, data, cacheKey);
            // Reset the limit.
            autocomplete.options.limit = limit;
          }
          else {
            // Clear the input field and hide the selector.
            autocomplete.clear();
          }
        });
        // Hide the selector when the input is emptied.
        input.addEventListener('search', function (event) {
          if (event.target && event.target.value === '' && autocomplete.selectorIsOpen()) {
            // Clear the input field and hide the selector.
            autocomplete.clear();
          }
        });
      }

      // Add an autocomplete widget to the select elements.
      var elements = context.querySelectorAll('[data-ocha-reliefweb-select]:not([data-ocha-reliefweb-select-processed])');
      for (var i = 0, l = elements.length; i < l; i++) {
        var element = elements[i];
        element.setAttribute('data-ocha-reliefweb-select-processed', '');
        enableAutocomplete(element);
      }
    }
  };

})(Drupal);
