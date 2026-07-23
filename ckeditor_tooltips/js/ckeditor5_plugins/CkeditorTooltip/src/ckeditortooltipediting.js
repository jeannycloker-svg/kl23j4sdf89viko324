import { Plugin } from 'ckeditor5/src/core';
import CkeditorTooltipCommand from "./ckeditortooltipcommand";
import { uid } from 'ckeditor5/src/utils';

export default class CkeditorTooltipEditing extends Plugin {
  init() {
    this._defineSchema();
    this._defineConverters();

    this.editor.commands.add(
      'addCkeditorTooltip', new CkeditorTooltipCommand(this.editor)
    );
  }

  _defineSchema() {
    const schema = this.editor.model.schema;

    // Extend the text node's schema to accept the data-tippy-content attribute.
    schema.extend('$text', {
      allowAttributes: [
        'data-tippy-content',
        'data-tooltip-title',
        'data-tooltip-content',
        'data-tooltip-placeholder-is-selection',
        'data-tooltip-placeholder-is-selection-text',
        'data-tooltip-data'
      ]
    });
  }

  _defineConverters() {
    const conversion = this.editor.conversion;

    // Conversion from a model attribute to a view element.
    // The attribute data-tooltip-data is mandatory.
    conversion.for('downcast').attributeToElement({
      model: 'data-tooltip-data',

      // Callback function provides access to the model attribute value
      // and the DowncastWriter.
      view: (modelAttributeValue, conversionApi) => {
        const {writer} = conversionApi;

        // Do not convert empty attributes (lack of value means no mention).
        if (!modelAttributeValue) {
          return;
        }

        const tooltipTitle = modelAttributeValue[0].tooltipTitle;
        const tooltipContent = modelAttributeValue[0].tooltipContent;

        let tooltipPlaceholderIsSelection = false;
        let tooltipPlaceholderIsSelectionText = "";
        if (modelAttributeValue[0].placeholderIsSelection) {
          tooltipPlaceholderIsSelection = modelAttributeValue[0].placeholderIsSelection;
          tooltipPlaceholderIsSelectionText = modelAttributeValue[0].placeholderIsSelectionText;
        }

        // In case I'll need it.
        const dataAttrValue = [{
          'tooltipTitle': tooltipTitle,
          'tooltipContent': tooltipContent,
          'placeholderIsSelection': tooltipPlaceholderIsSelection,
          'placeholderIsSelectionText': tooltipPlaceholderIsSelectionText,
        }];

        return writer.createAttributeElement(
          'span',
          {
            'class': 'ckeditor-tooltip-text',
            'data-tippy-content': '<span class="tooltip-title">' + tooltipTitle + '</span>' + tooltipContent,
            'data-tooltip-title': tooltipTitle,
            'data-tooltip-content': tooltipContent,
            'data-tooltip-placeholder-is-selection': tooltipPlaceholderIsSelection,
            'data-tooltip-placeholder-is-selection-text': tooltipPlaceholderIsSelectionText,
            'data-tooltip-data': encodeURIComponent(JSON.stringify(dataAttrValue)),
          }
        );
      },
    });

    // Conversion from a view element to a model attribute.
    conversion.for('upcast').elementToAttribute({
      view: {
        name: 'span',
        attributes: {
          'data-tippy-content': true,
          'data-tooltip-title': true,
          'data-tooltip-content': true,
          'data-tooltip-placeholder-is-selection': true,
          'data-tooltip-placeholder-is-selection-text': true,
          'data-tooltip-data': true
        }
      },
      model: {
        key: 'data-tooltip-data',

        // Callback function provides access to the view element.
        // value: viewElement => {
        //   const content = viewElement.getAttribute('data-tippy-content');
        //   return content;
        // }

        value: viewElement => {
          const content = [{
            'tooltipTitle': viewElement.getAttribute('data-tooltip-title'),
            'tooltipContent': viewElement.getAttribute('data-tooltip-content'),
            'placeholderIsSelection': viewElement.getAttribute('data-tooltip-placeholder-is-selection'),
            'placeholderIsSelectionText': viewElement.getAttribute('data-tooltip-placeholder-is-selection-text'),
          }
          ];
          return content;
        }

      },
    });

  }
}
