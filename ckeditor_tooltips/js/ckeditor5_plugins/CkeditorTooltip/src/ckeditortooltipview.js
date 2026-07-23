import {
  View,
  createLabeledTextarea,
  LabeledFieldView,
  createLabeledInputText,
  ButtonView,
  submitHandler,
  FocusCycler
  } from 'ckeditor5/src/ui';
import { FocusTracker, KeystrokeHandler } from 'ckeditor5/src/utils';
import { IconCheck, IconCancel, IconRemove } from '@ckeditor/ckeditor5-icons';

export default class FormView extends View {
  constructor(locale) {
    super(locale);

    // Adding a keystroke handler and a focus tracker.
    this.focusTracker = new FocusTracker();
    this.keystrokes = new KeystrokeHandler();

    // Creating input fields.
    this.titleInputView = this._createInput('Title');
    this.titleInputView.isEnabled = true;
    this.titleInputView.fieldView.value = '';
    this._hideTitle = false;
    this.contentInputView = this._createTextarea('Content');

    // Create the save button.
    this.saveButtonView = this._createButton(
      Drupal.t('Save'),
      IconCheck,
      'ck-button-save',
    );
    // Set the type to 'submit', which will trigger the submit event on the 
    // entire form when clicked.
    this.saveButtonView.type = 'submit';

    // Create the cancel button.
    this.cancelButtonView = this._createButton(
      Drupal.t('Cancel'),
      IconCancel,
      'ck-button-cancel',
    );
    // Delegate ButtonView#execute to FormView#cancel.
    this.cancelButtonView.delegate('execute').to(this, 'cancel');

    // Create the delete button.
    this.deleteButtonView = this._createButton(
      Drupal.t('Delete'),
      IconRemove,
      'ck-button-delete',
    );
    // Delegate ButtonView#execute to FormView#cancel.
    this.deleteButtonView.delegate('execute').to(this, 'delete');

    // We put all our input and button views in the collection, and use it to
    // update the FormView template with its newly created children.
    this.childViews = this.createCollection([
      this.titleInputView,
      this.contentInputView,
      this.saveButtonView,
      this.cancelButtonView,
      this.deleteButtonView
    ]);

    this._focusables = this.createCollection([
      this.titleInputView,
      this.contentInputView,
      this.saveButtonView,
      this.cancelButtonView,
      this.deleteButtonView
    ]);

    // The FocusCycler will allow the user to navigate through all the children
    // of our form view, cycling over them.
    this._focusCycler = new FocusCycler({
      focusables: this.childViews,
      focusTracker: this.focusTracker,
      keystrokeHandler: this.keystrokes,
      actions: {
        // Navigate form fields backwards using the Shift + Tab keystroke.
        focusPrevious: 'shift + tab',

        // Navigate form fields forwards using the Tab key.
        focusNext: 'tab'
      }
    });

    // ck is a standard. The other one is for us. To make sure our view is
    // focusable, let’s add tabindex="-1".
    this.setTemplate({
      tag: 'form',
      attributes: {
        class: ['ck', 'ck-cke-tooltip-form'],
        tabindex: '-1'
      },
      children: this.childViews
    });
  }

  /*
   * We will use a helper submitHandler() function there, which intercepts a
   * native DOM submit event, prevents the default web browser behavior
   * (navigation and page reload) and fires the submitted event on a view instead.
   */
  render() {
    super.render();

    // Submit the form when the user clicked the save button
    // or pressed enter in the input.
    submitHandler({
      view: this
    });

    if (this._hideTitle && this.titleInputView?.element) {
      this.titleInputView.element.style.display = 'none';
      this.titleInputView.element.setAttribute('aria-hidden', 'true');
    }
    // Register only focusables.
    this._focusables.forEach(view => {
      this.focusTracker.add(view.element);
    });

    // Start listening for the keystrokes coming from #element.
    this.keystrokes.listenTo(this.element);
  }

  /*
   * Destroy both the focus tracker and the keystroke handler. It will ensure
   * that when the user kills the editor, our helpers “die” too, preventing any
   * memory leaks.
   */
  destroy() {
    super.destroy();
    this.focusTracker.destroy();
    this.keystrokes.destroy();
  }

  /*
   * focus() will focus on the first child of our abbreviation input view each
   * time the form is added to the editor. This is just a taste of what focus
   * tracking can do in CKEditor 5.
   *
   * Focus tracking: https://ckeditor.com/docs/ckeditor5/latest/framework/deep-dive/ui/focus-tracking.html
   */
  focus() {
    // Uncomment this line if you just want to focus on the first field in the
    // ballon (in this case, the title).
    //this.childViews.first.focus();

    // If the title text field is enabled, focus it.
    if (this.titleInputView.isEnabled) {
      this.titleInputView.focus();
    }
    // Focus the content field if the former is disabled.
    else {
      this.contentInputView.focus();
    }
  }

  /*
   * Creating input fields.
   *
   * https://ckeditor.com/docs/ckeditor5/latest/api/module_ui_labeledfield_utils.html#function-createLabeledInputText
   */
  _createInput(label) {
    // createLabeledInputText is a helper coming from the CKEditor UI library.
    // createLabeledTextarea
    const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);
    labeledInput.label = label;
    return labeledInput;
  }

  /*
   * Creating Text Area
   * https://ckeditor.com/docs/ckeditor5/latest/api/module_ui_labeledfield_utils.html#constant-createLabeledTextarea
   */
  _createTextarea(label) {
    const labeledTextarea = new LabeledFieldView(this.locale, createLabeledTextarea);
    labeledTextarea.fieldView.set({
      rows: 6,
      class: 'ck-tooltip-textarea',
    });
    labeledTextarea.label = label;
    return labeledTextarea;
  }

  /*
   * Creating form buttons.
   */
  _createButton(label, icon, className) {
    const button = new ButtonView();

    button.set({
      label,
      icon,
      tooltip: true,
      class: className
    });

    return button;
  }

}
