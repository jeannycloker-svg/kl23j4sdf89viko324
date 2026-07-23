import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests basic module functionality.
 */
test.describe('Basic', () => {
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
    await ckeditor5.copyMarkupAsRichTextToClipboard('Before Hello World');
  });

  test('Basic paste filter functionality', async ({ ckeditor5 }) => {
    const expectedMarkup = '<p>\n    Before World World\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(
      'ckeditor5_paste_filter_test',
      expectedMarkup,
    );
  });

  test('JavaScript aggregation', async ({ page, ckeditor5 }) => {
    const scriptTag = 'script[src*="build/pasteFilter.js"]';
    await test.step('Check for individual script without aggregation', async () => {
      await page.goto('/node/add/ckeditor5_paste_filter_test');
      await expect(page.locator(scriptTag)).toHaveCount(1);
    });

    await test.step('Enable aggregation', async () => {
      await page.goto('/admin/config/development/performance');
      await page
        .getByRole('checkbox', { name: 'Aggregate JavaScript files' })
        .check();
      await page.getByRole('button', { name: 'Save configuration' }).click();
    });

    await test.step('Ensure plugin functionality is intact', async () => {
      const expectedMarkup = '<p>\n    Before World World\n</p>';
      await ckeditor5.pasteRichTextIntoEditor('ckeditor5_paste_filter_test', expectedMarkup);
    });

    await expect(page.locator(scriptTag)).toHaveCount(0);
  });
});
