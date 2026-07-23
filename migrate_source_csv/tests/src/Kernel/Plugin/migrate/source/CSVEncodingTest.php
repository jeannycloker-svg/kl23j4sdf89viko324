<?php

namespace Drupal\Tests\migrate_source_csv\Kernel\Plugin\migrate\source;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Tests the CSV source plugin with encoding filters.
 *
 * @group migrate_source_csv
 */
class CSVEncodingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_source_csv'];

  /**
   * Tests CSV encoding conversion using stream filters.
   */
  public function testEncodingFilter() {
    // Create a CSV string encoded in ISO-8859-1 (Latin-1).
    // The word 'mañana' contains a special character.
    $csv_content = iconv('UTF-8', 'ISO-8859-1', "id,text\n1,mañana");
    $file_path = 'public://test_encoding.csv';
    file_put_contents($file_path, $csv_content);

    // Configure the plugin to use the iconv stream filter.
    $configuration = [
      'path' => $file_path,
      'ids' => ['id'],
      'filters' => [
      [
        'name' => 'convert.iconv.ISO-8859-1/UTF-8',
      ],
      ],
    ];

    $migration = $this->createMock('\Drupal\migrate\Plugin\MigrationInterface');
    $plugin = new CSV($configuration, 'csv', [], $migration);

    $iterator = $plugin->initializeIterator();
    $row = $iterator->current();

    // Assert that the text was correctly converted back to UTF-8.
    $this->assertEquals('mañana', $row['text']);
  }

}
