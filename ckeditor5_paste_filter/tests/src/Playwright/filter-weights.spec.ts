import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

/**
 * Tests filter weights.
 */
test.describe('Filter weights', () => {
  const textFormatName = `ckeditor5_paste_filter_weights_test`;

  test.beforeAll(
    'Set up test site with CKEditor 5 Paste Filter',
    async ({ browser, drupalSite }) => {
      const page = await browser.newPage();
      const drupal: Drupal = new Drupal({ page, drupalSite });
      await drupal.installModules(['ckeditor5_paste_filter']);
      await page.close();
    },
  );

  test('Verify numbering of filter weights', async ({ page, drupal, ckeditor5 }) => {
    await drupal.loginAsAdmin();
    await ckeditor5.createNewTextFormat(textFormatName);

    const highestWeight = await page
      .locator(
        '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
      )
      .last()
      .inputValue();
    const zeroIndexedNumberOfFilters =
      (await page
        .locator(
          '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
        )
        .count()) - 1;

    await test.step('Add new filter and check its weight', async () => {
      await page.getByRole('button', { name: 'Add another filter' }).click();
      await expect(
        page.getByRole('cell', { name: 'Search expression Replacement' }),
      ).toHaveCount(1);

      await expect(
        page
          .locator(
            '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
          )
          .nth(zeroIndexedNumberOfFilters + 1),
      ).toHaveValue(`${parseInt(highestWeight) + 1}`);
    });

    await test.step('Add another new filter and check both weights again', async () => {
      await page.getByRole('button', { name: 'Add another filter' }).click();
      await expect(
        page.getByRole('cell', { name: 'Search expression Replacement' }),
      ).toHaveCount(2);

      await expect(
        page
          .locator(
            '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
          )
          .nth(zeroIndexedNumberOfFilters + 1),
      ).toHaveValue(`${parseInt(highestWeight) + 1}`);

      await expect(
        page
          .locator(
            '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
          )
          .nth(zeroIndexedNumberOfFilters + 2),
      ).toHaveValue(`${parseInt(highestWeight) + 2}`);
    });

    await page.getByRole('button', { name: 'Save configuration' }).click();
    await expect(page.getByLabel('Status message')).toContainText(
      'Added text format',
    );
  });
});
