<?php

declare(strict_types=1);

namespace Drupal\Tests\admin_toolbar_search\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that module configuration links are included in the search links.
 *
 * Covers the addition of module configuration links ('configure' key in
 * *.info.yml files) to the Admin Toolbar Search suggestions, so that modules
 * can be found by searching for their name, not just by their route path.
 *
 * @group admin_toolbar
 * @group admin_toolbar_search
 *
 * @see \Drupal\admin_toolbar_search\SearchLinks::getLinks()
 */
class AdminToolbarSearchModuleConfigLinksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_toolbar_search',
    'admin_toolbar',
    'admin_toolbar_tools',
    // Test-only fixture modules, see the 'tests/modules' directory.
    'admin_toolbar_search_test',
    'admin_toolbar_search_test_invalid_route',
  ];

  /**
   * The URL of the search links AJAX endpoint.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $searchUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->searchUrl = Url::fromRoute('admin_toolbar.search');
  }

  /**
   * Requests the search links endpoint and decodes the JSON response.
   *
   * @return array<int, array{labelRaw: string, value: string}>
   *   The decoded array of search links.
   */
  protected function getSearchLinks(): array {
    $this->drupalGet($this->searchUrl);
    $this->assertSession()->statusCodeEquals(200);
    $content = $this->getSession()->getPage()->getContent();
    $decoded = json_decode($content, TRUE);
    $this->assertIsArray($decoded, 'The search links endpoint returned valid JSON.');
    return $decoded;
  }

  /**
   * Asserts whether a link with the given label exists in the search links.
   *
   * @param array<int, array{labelRaw: string, value: string}> $links
   *   The decoded search links, as returned by ::getSearchLinks().
   * @param string $label
   *   The expected 'labelRaw' value to look for.
   * @param bool $should_exist
   *   Whether the label is expected to be present or absent.
   * @param string|null $expected_path
   *   (optional) If provided, and the link is expected to exist, also assert
   *   that its 'value' ends with this internal path.
   */
  protected function assertSearchLinkLabel(array $links, string $label, bool $should_exist, ?string $expected_path = NULL): void {
    $matches = array_values(array_filter($links, static fn(array $link): bool => $link['labelRaw'] === $label));

    if ($should_exist) {
      $this->assertNotEmpty($matches, sprintf('Expected to find a search link labelled "%s".', $label));
      if ($expected_path !== NULL) {
        $this->assertStringEndsWith($expected_path, $matches[0]['value'], sprintf('The search link labelled "%s" points to the expected path.', $label));
      }
    }
    else {
      $this->assertEmpty($matches, sprintf('Did not expect to find a search link labelled "%s".', $label));
    }
  }

  /**
   * Tests a module's configuration link is included when the user has access.
   *
   * A module's name (from its 'configure' key) should be searchable, even
   * when its configuration route path itself does not contain the search
   * term. This also covers the reported issue where a module (such as
   * Glossify) could not be found by searching for its name.
   */
  public function testModuleConfigurationLinkVisibleWithPermission(): void {
    $user = $this->drupalCreateUser([
      'use admin toolbar search',
      'access admin toolbar search test',
    ]);
    $this->drupalLogin($user);

    $links = $this->getSearchLinks();

    // The fixture module's configuration link should be present, using its
    // human readable name as the label, and pointing to its configure route.
    $this->assertSearchLinkLabel(
      $links,
      'Admin Toolbar Search Test',
      TRUE,
      '/admin/config/development/admin-toolbar-search-test'
    );
  }

  /**
   * Tests a module's configuration link is hidden without access.
   *
   * Even though the module is enabled and declares a 'configure' key, its
   * link should not be exposed to users who cannot access its configuration
   * route.
   */
  public function testModuleConfigurationLinkHiddenWithoutPermission(): void {
    // This user has access to the search itself, but not to the fixture
    // module's configuration route.
    $user = $this->drupalCreateUser(['use admin toolbar search']);
    $this->drupalLogin($user);

    $links = $this->getSearchLinks();

    $this->assertSearchLinkLabel($links, 'Admin Toolbar Search Test', FALSE);
  }

  /**
   * Tests that a module with an invalid/missing 'configure' route is skipped.
   *
   * If a module's 'configure' key references a route that does not exist,
   * SearchLinks::routeExists() should return FALSE and the module should be
   * silently skipped, without causing an error or exception.
   */
  public function testInvalidConfigureRouteIsSkippedGracefully(): void {
    $user = $this->drupalCreateUser(['use admin toolbar search']);
    $this->drupalLogin($user);

    // The request itself must succeed (no fatal error/exception), and the
    // invalid route's module must not appear in the results.
    $links = $this->getSearchLinks();
    $this->assertSearchLinkLabel($links, 'Admin Toolbar Search Test Invalid Route', FALSE);
  }

  /**
   * Tests newly installed modules become searchable without manual caching.
   *
   * Installing a module should invalidate the cached search links (tagged
   * with 'config:core.extension') and the module list needs to be re-scanned,
   * so its configuration link becomes searchable immediately, without any
   * additional cache clearing.
   */
  public function testNewlyInstalledModuleLinkAppearsAfterInstall(): void {
    // The root user (uid 1) bypasses all permission checks, including the
    // fixture module's own permission, so we can focus this test on the
    // module install/cache-invalidation behavior rather than access control.
    $this->drupalLogin($this->rootUser);

    // Before installing the module, its configuration link must be absent.
    $links = $this->getSearchLinks();
    $this->assertSearchLinkLabel($links, 'Admin Toolbar Search Test Restricted', FALSE);

    // Install the module. Enabling a module changes 'config:core.extension',
    // which is one of the cache tags used by SearchLinks::getLinks(), so the
    // cached links should be invalidated automatically.
    $this->container->get('module_installer')->install(['admin_toolbar_search_test_restricted']);
    $this->resetAll();

    // The newly installed module's configuration link should now be found,
    // without requiring any additional manual cache clearing.
    $links = $this->getSearchLinks();
    $this->assertSearchLinkLabel(
      $links,
      'Admin Toolbar Search Test Restricted',
      TRUE,
      '/admin/config/development/admin-toolbar-search-test-restricted'
    );
  }

}
