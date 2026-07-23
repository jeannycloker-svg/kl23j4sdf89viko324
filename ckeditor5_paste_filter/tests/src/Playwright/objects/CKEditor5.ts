import type { Page } from '@playwright/test';
import type { DrupalSite } from '../fixtures/DrupalSite';
import { expect, test } from '@playwright/test';
import { Drupal } from './Drupal';

export class CKEditor5 {
  readonly page: Page;
  readonly drupal: Drupal;

  constructor({ page, drupalSite }: { page: Page; drupalSite: DrupalSite }) {
    this.page = page;
    this.drupal = new Drupal({ page, drupalSite });
  }

  async copyMarkupAsRichTextToClipboard(markup: string) {
    await this.drupal.loginAsAdmin();
    await this.page.goto('/node/add/ckeditor5_paste_filter_test');
    await test.step('Insert test content via source editing area', async () => {
      await this.page.getByRole('button', { name: 'Source' }).click();
      await this.page
        .getByRole('textbox', { name: 'Source code editing area' })
        .fill(markup);
    });

    await test.step('Copy the content to clipboard from the visual editor', async () => {
      await this.page.getByRole('button', { name: 'Source' }).click();
      await this.page
        .getByRole('textbox', { name: 'Rich Text Editor. Editing' })
        .press('ControlOrMeta+a');
      await this.page
        .getByRole('textbox', { name: 'Rich Text Editor. Editing' })
        .press('ControlOrMeta+c');
    });
  }

  async pasteRichTextIntoEditor(
    textFormatName: string,
    expectedMarkup: string,
  ) {
    // Load a fresh node add form. We do this so that the <p> tag doesn't
    // get retained (and excluded from filtering), which would happen if we
    // pasted into the same editor that we copied from. We could also clear
    // the markup from the source editing area before pasting, but starting
    // with a fresh form is simpler.
    await this.page.goto('/node/add/ckeditor5_paste_filter_test');

    // If there is a text format select list, choose our text format. There may
    // not be a select list if there is only one text format available.
    if (await this.page.getByLabel('Text format').isVisible()) {
      await this.page.getByLabel('Text format').selectOption(textFormatName);
    }

    // Paste into the visual editor. This will trigger the paste filter behavior.
    await this.page
      .getByRole('textbox', { name: 'Rich Text Editor. Editing' })
      .press('ControlOrMeta+v');
    await this.page.getByRole('button', { name: 'Source' }).click();
    await expect(
      this.page.getByRole('textbox', { name: 'Source code editing area' }),
    ).toHaveValue(expectedMarkup);
  }

  // Log in as an admin before calling this.
  async createNewTextFormat(textFormatName: string) {
    await this.page.goto('/admin/config/content/formats/add');
    await this.page.getByRole('textbox', { name: 'Name' }).fill(textFormatName);
    await expect(
      this.page.locator('#edit-name-machine-name-suffix'),
    ).toContainText(textFormatName);
    await this.page
      .getByLabel('Text editor', { exact: true })
      .selectOption('ckeditor5');
    await this.page
      .getByRole('option', { name: 'available button Source.' })
      .press('ArrowDown');

    // Without this step, we can run into errors with subsequent steps since
    // the editor config entity might not exist yet.
    await test.step('Wait for source editing vertical tab', async () => {
      await expect(
        this.page.getByRole('link', { name: 'Source editing' }),
      ).toBeVisible();
    });

    await this.page.getByRole('link', { name: 'Paste filter' }).click();
    await this.page
      .locator(
        '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-enabled"]',
      )
      .check();
  }
}
