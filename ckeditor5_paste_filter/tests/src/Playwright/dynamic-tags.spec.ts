import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests dynamic replacements that were incompatible with our previous XSS
 * filtering. These replacements "look like" HTML tags, so were being filtered
 * out by the XSS filtering.
 *
 * @see https://www.drupal.org/project/ckeditor5_paste_filter/issues/3476210
 */
test.describe('Dynamic tag replacement', () => {
  const textFormatName = `ckeditor5_paste_filter_xss_test`;

  test.beforeAll(
    'Set up test site with CKEditor 5 Paste Filter',
    async ({ browser, drupalSite }) => {
      const page = await browser.newPage();
      const drupal: Drupal = new Drupal({ page, drupalSite });
      await drupal.installModules(['ckeditor5_paste_filter_test']);
      await page.close();
    },
  );

  test.beforeEach('Prepare pasteboard for testing', async ({ ckeditor5 }) => {
    await ckeditor5.copyMarkupAsRichTextToClipboard('<p>&nbsp;</p><h2>Testing</h2>');
  });

  test('Dynamic tag replacement', async ({ page, ckeditor5 }) => {
    await ckeditor5.createNewTextFormat(textFormatName);

    await test.step('Add filter that would have issues with XSS filtering', async () => {
      await page.getByRole('button', { name: 'Add another filter' }).click();
      await page
        .getByRole('cell', { name: 'Search expression Replacement' })
        .getByLabel('Search expression')
        .fill('<(\/?)h2>');
      await page
        .getByRole('cell', { name: 'Search expression <(\/?)h2>' })
        .getByLabel('Replacement')
        .fill('<$1h3>');
    });

    await page.getByRole('button', { name: 'Save configuration' }).click();
    await expect(page.getByLabel('Status message')).toContainText(
      'Added text format',
    );

    const expectedMarkup = "<h3>\n    Testing\n</h3>";
    await ckeditor5.pasteRichTextIntoEditor(textFormatName, expectedMarkup);
  });
});
