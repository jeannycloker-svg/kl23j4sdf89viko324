(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.ckeditor_tooltips = {
    attach: function (context) {

      // For the Frontend.
      $(once('ckeditor_tooltips', 'html')).each(function () {
        tippy('[data-tippy-content]', drupalSettings.ckeditor_tooltips);
      });

      // Insert link inside the textarea.
      // Until we don't find a way to add CKEditor to the dialog, we can use this.
      // $.fn.extend({
      //   insertAtCaret: function(myValue, myValueE) {
      //     return this.each(function(i) {
      //       if (document.selection) {
      //         //For browsers like Internet Explorer.
      //         this.focus();
      //         var sel = document.selection.createRange();
      //         sel.text = myValue + myValueE;
      //         this.focus();
      //       }
      //       else if (this.selectionStart || this.selectionStart === '0') {
      //         // For browsers like Firefox and Webkit based.
      //         var startPos = this.selectionStart;
      //         var endPos = this.selectionEnd;
      //         var scrollTop = this.scrollTop;
      //         this.value = this.value.substring(0, startPos)+myValue+this.value.substring(startPos,endPos)+myValueE+this.value.substring(endPos,this.value.length);
      //         this.focus();
      //         this.selectionStart = startPos + myValue.length;
      //         this.selectionEnd = ((startPos + myValue.length) + this.value.substring(startPos,endPos).length);
      //         this.scrollTop = scrollTop;
      //       }
      //       else {
      //         this.value += myValue;
      //         this.focus();
      //       }
      //     })
      //   }
      // });

      // $(once('ckeditor_tooltips', '.ckeditor-tooltip-add-link', context)).each(function () {
      //   if ($(this).length > 0) {
      //     $(this, context).on('click', function (ev) {
      //       ev.preventDefault();
      //       var textarea = $(this).parent().closest('.form-type-textarea').find('textarea'),
      //           text_to_insert = '<a href="ADD LINK">ADD TEXT</a>';
      //
      //       $(textarea).insertAtCaret(text_to_insert, "");
      //     });
      //   }
      // });
    }
  };

}(jQuery, Drupal, drupalSettings));
