import { Plugin } from 'ckeditor5/src/core';
import { ClipboardPipeline } from 'ckeditor5/src/clipboard';

export default class PasteFilter extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'PasteFilter';
  }

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ClipboardPipeline];
  }

  /**
   * @inheritDoc
   */
  init() {
    this.editor.plugins
      .get('ClipboardPipeline')
      .on('inputTransformation', (evt, data) => {
        const filters = this.editor.config.get('pasteFilter');
        if (!filters) {
          return;
        }

        let html = this.editor.data.htmlProcessor.toData(data.content);
        filters.forEach((filter) => {
          try {
            html = html.replace(
              new RegExp(filter.search, 'gimsu'),
              filter.replace,
            );
          } catch (e) {
            // eslint-disable-next-line no-console
            console.error(
              Drupal.t(
                'CKEditor 5 Paste Filter: Invalid regular expression "!error"',
                {
                  '!error': filter.search,
                },
              ),
            );
          }
        });

        data.content = this.editor.data.htmlProcessor.toView(html);
      });
  }
}
