import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests empty rows behavior.
 */
test.describe('Empty rows', () => {
  const textFormatName = `ckeditor5_paste_filter_empty_rows_test`;

  test.beforeAll(
    'Set up test site with CKEditor 5 Paste Filter',
    async ({ browser, drupalSite }) => {
      const page = await browser.newPage();
      const drupal: Drupal = new Drupal({ page, drupalSite });
      await drupal.installModules(['ckeditor5_paste_filter']);
      await page.close();
    },
  );

  test('Empty row handling', async ({ page, drupal, ckeditor5 }) => {
    await drupal.loginAsAdmin();
    await ckeditor5.createNewTextFormat(textFormatName);

    await test.step('Ensure no empty row when filters are already configured', async () => {
      await expect(
        page.getByRole('cell', { name: 'Search expression Replacement' }),
      ).toHaveCount(0);

      const zeroIndexedNumberOfFilters =
        (await page
          .locator(
            '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="search"]',
          )
          .count()) - 1;
      await expect(
        page
          .locator(
            '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="search"]',
          )
          .nth(zeroIndexedNumberOfFilters + 1),
      ).not.toHaveValue('');
    });

    await test.step('Empty all filter values', async () => {
      const selector =
        '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="search"],[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="replace"]';
      const textInputs = await page.locator(selector).all();

      for (const input of textInputs) {
        await input.fill('');
      }
    });

    await page.getByRole('button', { name: 'Save configuration' }).click();
    await expect(page.getByLabel('Status message')).toContainText(
      'Added text format',
    );

    await test.step('Ensure these is one empty row', async () => {
      await page.goto(`/admin/config/content/formats/manage/${textFormatName}`);
      await page.getByRole('link', { name: 'Paste filter' }).click();

      await expect(
        page.getByRole('cell', { name: 'Search expression Replacement' }),
      ).toHaveCount(1);
      await expect(
        page.locator(
          '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="search"]',
        ),
      ).toHaveValue('');
    });
  });
});
