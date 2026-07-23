import { Plugin } from 'ckeditor5/src/core';
import { ButtonView, ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';
import FormView from './ckeditortooltipview';
import getRangeText from './utils.js';
import icon from "../../../../icons/tooltip.svg";
import './styles.css';
import { findAttributeRange } from "ckeditor5/src/typing";

export default class CkeditorTooltipUI extends Plugin {
  static get requires() {
    return [ContextualBalloon];
  }

  // The balloon and the view initialization.
  init() {
    const editor = this.editor;

    // Create the balloon and the form view.
    this._balloon = this.editor.plugins.get(ContextualBalloon);
    this.formView = this._createFormView();

    // Register the button in the editor's UI component factory.
    editor.ui.componentFactory.add('CkeditorTooltip', () => {
      const button = new ButtonView();

      button.label = 'CKEditor Tooltips';
      button.icon = icon;
      button.tooltip = true;
      button.withText = false;

      // Show the UI on button click.
      this.listenTo(button, 'execute', () => {
        this._showUI();
      });

      return button;
    });
  }

  /*
   * Let’s write a basic _createFormView() function, just to create an instance
   * of our FormView class.
   */
  _createFormView() {
    const editor = this.editor;
    const formView = new FormView(editor.locale);

    // Submit the values from the modal window to the addCkeditorTooltip command.
    this.listenTo(formView, 'submit', () => {
      const value = {
        title: formView.titleInputView.fieldView.element.value,
        content: formView.contentInputView.fieldView.element.value
      };

      // Execute our command.
      // Check ckeditortooltipcommand.js and ckeditortooltipediting.js.
      editor.execute('addCkeditorTooltip', value);
      this._hideUI();
    });

    // Hide the form view after clicking the "Cancel" button.
    this.listenTo(formView, 'cancel', () => {
      this._hideUI();
    });

    // Delete the tooltip when clicking the "Delete" button.
    this.listenTo(formView, 'delete', () => {
      this._deleteTooltip();
      this._hideUI();
    });

    // Hide the form view when clicking outside the balloon.
    clickOutsideHandler({
      emitter: formView,
      activator: () => this._balloon.visibleView === formView,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideUI()
    });

    // Close the panel on esc key press when the form has focus.
    formView.keystrokes.set('Esc', (data, cancel) => {
      this._hideUI();
      cancel();
    });

    return formView;
  }

  /*
   * We will write a simple _hideUI() function, which will clear the input
   * field values and remove the view from our balloon.
   */
  _hideUI() {
    this.formView.contentInputView.fieldView.value = '';
    this.formView.titleInputView.fieldView.value = '';
    this.formView.element.reset();

    this._balloon.remove(this.formView);

    // Focus the editing view after closing the form view.
    this.editor.editing.view.focus();
  }

  /*
   * We also need to create a function, which will give us the target position
   * for our balloon from user’s selection. We need to convert the selected
   * view range into DOM range. We can use the viewRangeToDom() method to do so.
   */
  _getBalloonPositionData() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;
    let target = null;

    // Set a target position by converting view selection range to DOM.
    target = () => view.domConverter.viewRangeToDom(
      viewDocument.selection.getFirstRange()
    );

    return {
      target
    };
  }

  /*
   * Let’s write a _showUI() method which will show our UI elements by adding
   * the form view to our balloon and setting its position.
   */
  _showUI() {
    // Get the user selected text in the CKEditor.
    const selection = this.editor.model.document.selection;

    // Check the value of the command.
    const commandValue = this.editor.commands.get('addCkeditorTooltip').value;

    this._balloon.add({
      view: this.formView,
      position: this._getBalloonPositionData()
    });

    // Disable the wanted field when the selection is not collapsed.
    // this.formView.titleInputView.isEnabled = selection.getFirstRange().isCollapsed;

    // Fill the form using the state (value) of the command.
    if (commandValue) {
      this.formView.titleInputView.fieldView.value = commandValue.title;
      this.formView.contentInputView.fieldView.value = commandValue.content;
    }
    // If the command has no value, put the currently selected text (not collapsed)
    // in the content field.
    else {
      const selectedText = getRangeText(selection.getFirstRange()); // Check ./utils.js
      this.formView.contentInputView.fieldView.value = selectedText;
      // this.formView.titleInputView.fieldView.value = '';
    }

    this.formView.focus();
  }

  // Delete functionality.
  _deleteTooltip() {
    const model = this.editor.model;
    const selection = model.document.selection;

    if (selection.hasAttribute('data-tooltip-data')) {
      const attributeCkeditorContentValue = selection.getAttribute('data-tooltip-data');
      // Find the entire range containing the data-tippy-content.
      const ckeditorTooltipRange = findAttributeRange(
        selection.getFirstPosition(), 'data-tooltip-data', attributeCkeditorContentValue, model
      );
      // Delete it.
      model.change(writer => {
        writer.remove(ckeditorTooltipRange);
      });
    }
  }
}
