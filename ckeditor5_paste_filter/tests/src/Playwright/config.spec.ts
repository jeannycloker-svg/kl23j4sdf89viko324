import { expect } from '@playwright/test';
import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';
import { getModuleDir } from './utilities/DrupalFilesystem';

/**
 * Tests module configuration.
 */
test.describe('Config', () => {
  test.beforeAll(
    'Set up test site with CKEditor 5 Paste Filter',
    async ({ browser, drupalSite }) => {
      const page = await browser.newPage();
      const drupal: Drupal = new Drupal({ page, drupalSite });
      await drupal.installModules(['ckeditor5_paste_filter_test', 'config']);
      await page.close();
    },
  );

  test.beforeEach('Prepare pasteboard for testing', async ({ ckeditor5 }) => {
    await ckeditor5.copyMarkupAsRichTextToClipboard('Before Hello World');
  });

  test.afterEach('Reset config', async ({ drupal }) => {
    const moduleDir = await getModuleDir();
    await drupal.drush(
      `config:import --yes --partial --source=${moduleDir}/ckeditor5_paste_filter/tests/ckeditor5_paste_filter_test/config/install`,
    );
  });

  test('Enable a disabled filter', async ({ page, ckeditor5 }) => {
    await test.step('Enable the second filter that replaces "Before" with "After".', async () => {
      await page.goto(
        '/admin/config/content/formats/manage/ckeditor5_paste_filter_test',
      );
      await page.getByRole('link', { name: 'Paste filter' }).click();
      await page
        .locator(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-enabled"]',
        )
        .check();
      await page.getByRole('button', { name: 'Save configuration' }).click();
    });

    const expectedMarkup = '<p>\n    After World World\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(
      'ckeditor5_paste_filter_test',
      expectedMarkup,
    );
  });

  test('Update enabled filters and update filter weights', async ({
    page,
    ckeditor5,
  }) => {
    await page.goto(
      '/admin/config/content/formats/manage/ckeditor5_paste_filter_test',
    );
    await page.getByRole('link', { name: 'Paste filter' }).click();
    await page
      .locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-enabled"]',
      )
      .check();
    await page
      .locator(
        '#edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-enabled',
      )
      .uncheck();

    await page.getByText('Before', { exact: true }).fill('Hello');
    await page
      .locator('#paste-filters-wrapper')
      .getByRole('button', { name: 'Show row weights' })
      .click();
    await page
      .getByLabel('Weight for filter replacing Hello')
      .selectOption('0');
    await page
      .getByLabel('Weight for filter replacing Before')
      .selectOption('-4');
    await page.getByRole('button', { name: 'Add another filter' }).click();
    await expect(
      page.getByLabel('Weight for filter replacing Hello'),
    ).toHaveValue('0');
    await expect(
      page.getByLabel('Weight for filter replacing Before'),
    ).toHaveValue('-4');
    // The initial weights should be 1,2,3 before manipulation, so this row should still be 3.
    await expect(
      page.getByLabel('Weight for filter replacing Third!'),
    ).toHaveValue('3');
    await page.getByRole('button', { name: 'Save configuration' }).click();

    const expectedMarkup = '<p>\n    Before After World\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(
      'ckeditor5_paste_filter_test',
      expectedMarkup,
    );
  });

  test('Config form validation and empty row handling', async ({
    page,
    ckeditor5,
  }) => {
    await test.step('Search expression required validation', async () => {
      await page.goto(
        '/admin/config/content/formats/manage/ckeditor5_paste_filter_test',
      );
      await page.getByRole('link', { name: 'Paste filter' }).click();

      await page
        .locator(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-search"]',
        )
        .fill('');

      await page.getByRole('button', { name: 'Save configuration' }).click();
      await expect(page.locator('[data-drupal-messages]')).toContainText(
        'The Search expression field is required. To remove the filter, empty both the Search expression and Replacement fields.',
      );
    });

    await test.step('Remove first filter by making it an empty row; enable and update the second filter replacement', async () => {
      await page
        .locator(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-replace"]',
        )
        .fill('');
      await page
        .locator(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-enabled"]',
        )
        .check();
      await page
        .locator(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-replace"]',
        )
        .fill('Aloha');
      await page.getByRole('button', { name: 'Save configuration' }).click();
    });

    const expectedMarkup = '<p>\n    Aloha Hello World\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(
      'ckeditor5_paste_filter_test',
      expectedMarkup,
    );
  });

  test('Disable the plugin', async ({ page, ckeditor5 }) => {
    await page.goto(
      '/admin/config/content/formats/manage/ckeditor5_paste_filter_test',
    );
    await page.getByRole('link', { name: 'Paste filter' }).click();
    await page
      .locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-enabled"]',
      )
      .uncheck();
    await page.getByRole('button', { name: 'Save configuration' }).click();

    // Ensure the filter table, help, and "Add another" button are not
    // visible when the plugin is disabled.
    await expect(
      page.locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"]',
      ),
    ).toHaveCount(0);
    await expect(
      page.locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-help"]',
      ),
    ).toHaveCount(0);
    await expect(
      page.locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions"]',
      ),
    ).toHaveCount(0);

    const expectedMarkup = '<p>\n    Before Hello World\n</p>';
    await ckeditor5.pasteRichTextIntoEditor(
      'ckeditor5_paste_filter_test',
      expectedMarkup,
    );
  });
});
