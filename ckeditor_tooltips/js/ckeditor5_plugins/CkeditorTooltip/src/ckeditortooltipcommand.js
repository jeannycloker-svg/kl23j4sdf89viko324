import { Command } from 'ckeditor5/src/core';
import { findAttributeRange } from 'ckeditor5/src/typing';
import { toMap } from 'ckeditor5/src/utils';
import getRangeText from './utils.js';

export default class CkeditorTooltipCommand extends Command {

  /*
   * Thanks to the command’s refresh() method, we can observe the state and the
   * value of our command not just when the user presses the button, but
   * whenever any changes are made in the editor.
   */
  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const firstRange = selection.getFirstRange();

    // THIS MEANS: when the user select after the tippy element.
    // This is checking what is the user selecting.
    // When the selection is collapsed, the command has a value
    // if the caret is in an data-tooltip-data.
    if (firstRange.isCollapsed) {
      // Get the content.
      if (selection.hasAttribute('data-tooltip-data')) {
        const attributeCkeditorContentValue = selection.getAttribute('data-tooltip-data');

        // Find the entire range containing the data-tooltip-data under the
        // caret position.
        const ckeditorTooltipRange = findAttributeRange(
          selection.getFirstPosition(), 'data-tooltip-data', attributeCkeditorContentValue, model
        );

        const titleValue = attributeCkeditorContentValue[0].tooltipTitle;
        const titleContent = attributeCkeditorContentValue[0].tooltipContent;

        const tooltipPlaceholderIsSelection = attributeCkeditorContentValue[0].placeholderIsSelection;
        const tooltipPlaceholderIsSelectionText = attributeCkeditorContentValue[0].placeholderIsSelectionText;

        // This value is passed to addCkeditorTooltip command.
        this.value = {
          selectedText: getRangeText(ckeditorTooltipRange),
          tooltipcontent: '<span class="tooltip-title">' + titleValue + '</span>' + titleContent,
          title: titleValue,
          content: titleContent,
          placeholderIsSelection: tooltipPlaceholderIsSelection,
          placeholderIsSelectionText: tooltipPlaceholderIsSelectionText,
          range: ckeditorTooltipRange,
        };
      }
      else {
        // This value is returned to addCkeditorTooltip command if the cursor is
        // not at the tooltip text in the CKEditor.
        this.value = null;
      }
    }
    // THIS MEANS: when the user selects more text.
    // When the selection is not collapsed, the command has a value if the selection
    // contains a subset of a single data-tippy-content or an entire data-tippy-content.
    else {
      if (selection.hasAttribute('data-tooltip-data')) {
        const attributeCkeditorContentValue = selection.getAttribute('data-tooltip-data');

        // Find the entire range containing the data-tippy-content
        // under the caret position.
        const ckeditorTooltipRange = findAttributeRange(
          selection.getFirstPosition(), 'data-tooltip-data', attributeCkeditorContentValue, model
        );

        const titleValue = attributeCkeditorContentValue[0].tooltipTitle;
        const titleContent = attributeCkeditorContentValue[0].tooltipContent;

        const tooltipPlaceholderIsSelection = attributeCkeditorContentValue[0].placeholderIsSelection;
        const tooltipPlaceholderIsSelectionText = attributeCkeditorContentValue[0].placeholderIsSelectionText;

        if (ckeditorTooltipRange.containsRange(firstRange, true)) {
          this.value = {
            selectedText: getRangeText(firstRange),
            tooltipcontent: '<span class="tooltip-title">' + titleValue + '</span>' + titleContent,
            title: titleValue,
            content: titleContent,
            placeholderIsSelection: tooltipPlaceholderIsSelection,
            placeholderIsSelectionText: tooltipPlaceholderIsSelectionText,
            range: firstRange
          };
        }
        else {
          this.value = null;
        }
      }
      else {
        this.value = null;
      }
    }

    // The command is enabled when the "data-tooltip-data" attribute
    // can be set on the current model selection.
    this.isEnabled = model.schema.checkAttributeInSelection(
      selection, 'data-tooltip-data'
    );
  }

  // This is the command execution.
  // It is executed when the user saves the balloon.
  execute({title, content}) {
    const model = this.editor.model;
    const selection = model.document.selection;

    // Collect all attributes of the user selection.
    const attributes = toMap(selection.getAttributes());

    // Change the placeholder text based on what the user selects. If it doesn't
    // select anything, show the predefined one.
    let placeholder_text = 'i';
    let tooltipPlaceholderIsSelection = false;
    let tooltipPlaceholderIsSelectionText = "";
    const tooltipData = selection.getAttribute('data-tooltip-data');

    if (tooltipData) {
      tooltipPlaceholderIsSelection = tooltipData[0].placeholderIsSelection;

      if (tooltipPlaceholderIsSelection === 'true') {
        tooltipPlaceholderIsSelectionText = tooltipData[0].placeholderIsSelectionText;
        // Change the placeholder text with the selected one.
        placeholder_text = tooltipPlaceholderIsSelectionText;
      }
    }

    model.change(writer => {
      // If selection is collapsed then update the selected abbreviation
      // or insert a new one at the place of caret.
      if (selection.isCollapsed) {
        // When a collapsed selection is inside text with the "data-tooltip-data" attribute,
        // update its text and title.
        // I am adding the data-tooltip-data so that I can use it in the
        // attributeToElement in the ckeditortooltipediting.js.
        if (this.value) {
          const {end: positionAfter} = model.insertContent(
            writer.createText(
              placeholder_text,
              {
                'data-tippy-content': '<span class="tooltip-title">' + title + '</span>' + content,
                'data-tooltip-title': title,
                'data-tooltip-content': content,
                'data-tooltip-placeholder-is-selection': tooltipPlaceholderIsSelection,
                'data-tooltip-placeholder-is-selection-text': tooltipPlaceholderIsSelectionText,
                'data-tooltip-data': [{
                  'tooltipTitle': title,
                  'tooltipContent': content,
                  'placeholderIsSelection': tooltipPlaceholderIsSelection,
                  'placeholderIsSelectionText': tooltipPlaceholderIsSelectionText,
                }],
              }
            ),
            this.value.range
          );

          writer.setSelection(positionAfter);
        }
        else if (content !== '') {
          const firstPosition = selection.getFirstPosition();

          // Inject the new text node with the abbreviation text with all selection attributes.
          // I am adding the data-tooltip-data so that I can use it in the
          // attributeToElement in the ckeditortooltipediting.js.
          const {end: positionAfter} = model.insertContent(
            writer.createText(
              placeholder_text,
              {
                'data-tippy-content': '<span class="tooltip-title">' + title + '</span>' + content,
                'data-tooltip-title': title,
                'data-tooltip-content': content,
                'data-tooltip-placeholder-is-selection': tooltipPlaceholderIsSelection,
                'data-tooltip-placeholder-is-selection-text': tooltipPlaceholderIsSelectionText,
                'data-tooltip-data': [{
                  'tooltipTitle': title,
                  'tooltipContent': content,
                  'placeholderIsSelection': tooltipPlaceholderIsSelection,
                  'placeholderIsSelectionText': tooltipPlaceholderIsSelectionText,
                }],
              }
            ),
            firstPosition
          );

          // Put the selection at the end of the inserted abbreviation.
          writer.setSelection(positionAfter);
        }

        // Remove the "data-tooltip-data" attribute from the selection. It stops
        // adding a new content into the data-tippy-content if the user starts to type.
        writer.removeSelectionAttribute('data-tooltip-data');
      }
      else {
        /*
         * Write this text in the CKEditor if the user selects some text.
         */
        // If the selection has non-collapsed ranges,
        // change the attribute on nodes inside those ranges
        // omitting nodes where the "data-tippy-content" attribute is disallowed.
        const ranges = model.schema.getValidRanges(
          selection.getRanges(), 'data-tippy-content'
        );

        for (const range of ranges) {
          const selectedText = getRangeText(range);

          model.insertContent(
            writer.createText(
              selectedText,
              {
                'data-tippy-content': '<span class="tooltip-title">' + title + '</span>' + content,
                'data-tooltip-title': title,
                'data-tooltip-content': content,
                'data-tooltip-placeholder-is-selection': true,
                'data-tooltip-placeholder-is-selection-text': selectedText,
                'data-tooltip-data': [{
                  'tooltipTitle': title,
                  'tooltipContent': content,
                  'placeholderIsSelection': true,
                  'placeholderIsSelectionText': selectedText,
                }],
              }
            ),
            range
          );
        }

        // Remove the "data-tooltip-data" attribute from the selection. It stops
        // adding a new content into the data-tippy-content if the user starts to type.
        writer.removeSelectionAttribute('data-tooltip-data');
      }

    });
  }
}
