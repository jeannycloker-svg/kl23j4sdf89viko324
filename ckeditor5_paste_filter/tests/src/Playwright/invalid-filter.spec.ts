import { ConsoleMessage, expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests behavior of invalid filters (bad regular expression).
 */
test.describe('Invalid filter', () => {
  const textFormatName = `ckeditor5_paste_filter_invalid_test`;
  const invalidFilter = 'invalid/\\';

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

  test('Invalid filter handling', async ({ page, ckeditor5 }) => {
    await ckeditor5.createNewTextFormat(textFormatName);

    await test.step('Add invalid filter', async () => {
      await page.getByRole('button', { name: 'Add another filter' }).click();
      await page
        .getByRole('cell', { name: 'Search expression Replacement' })
        .getByLabel('Search expression')
        .fill(invalidFilter);
    });

    await page.getByRole('button', { name: 'Save configuration' }).click();
    await expect(page.getByLabel('Status message')).toContainText(
      'Added text format',
    );

    const consoleMessages: ConsoleMessage[] = [];
    test.step('Listen for console messages', async () => {
      page.on('console', (msg) => {
        consoleMessages.push(msg);
      });
    });

    const expectedMarkup =
      '<p>\n    Test <strong>content </strong><a href="https://www.example.com/">for</a> the <em>paste filter</em> functionality.\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(textFormatName, expectedMarkup);
    expect(consoleMessages).toHaveLength(1);
    expect(consoleMessages[0].type()).toBe('error');
    expect(consoleMessages[0].text()).toBe(
      `CKEditor 5 Paste Filter: Invalid regular expression "${invalidFilter}"`,
    );
  });
});
