import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests defaults when setting up initially.
 */
test.describe('Defaults', () => {
  const textFormatName = `ckeditor5_paste_filter_defaults_test`;

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
    const fillText =
      '<p class="MsoNormal"><a name="OLE_LINK12"><span style="mso-bookmark:OLE_LINK13;">Test <strong>content </strong></span></a><a href="https://www.example.com/"><span style="mso-bookmark:OLE_LINK13;">for</span></a><span style="mso-bookmark:OLE_LINK13;"> the </span><em><span style="mso-bookmark:OLE_LINK13;">paste filter</span></em><span style="mso-bookmark:OLE_LINK13;"> functionality.</span><o:p></o:p></p>';
    await ckeditor5.copyMarkupAsRichTextToClipboard(fillText);
  });

  test('Test default paste filters', async ({ page, ckeditor5 }) => {
    await ckeditor5.createNewTextFormat(textFormatName);

    await test.step('Wait for Paste Filter AJAX to finish', async () => {
      await expect(
        page.getByRole('button', { name: 'Add another filter' }),
      ).toBeVisible();
    });

    await page.getByRole('button', { name: 'Save configuration' }).click();
    await expect(page.getByLabel('Status message')).toContainText(
      'Added text format',
    );

    const expectedMarkup =
      '<p>\n    Test <strong>content </strong><a href="https://www.example.com/">for</a> the <em>paste filter</em> functionality.\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(textFormatName, expectedMarkup);
  });
});
