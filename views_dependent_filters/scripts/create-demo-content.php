<?php

/**
 * @file
 * Script to seed demo content for manually exercising views_dependent_filter.
 *
 * Seeds demo content for manually exercising views_dependent_filter in the
 * browser, using the "vdf_test" view and "vdf_test_cont" content type that
 * are already configured on this site (see config/sync/views.view.vdf_test
 * .yml, node.type.vdf_test_cont.yml, taxonomy.vocabulary.vocab1/2.yml).
 */

// phpcs:disable Drupal.Files.LineLength.TooLong,Drupal.Commenting.DocComment.ShortFullStop
/*
 * Run with:
 *   drush scr web/modules/contrib/views_dependent_filters/scripts/create-demo-content.php
 *
 * Idempotent: re-running it looks up existing terms/nodes by name and only
 * creates what's missing, then makes sure the "vdf_test" view's
 * controller_values point at the real term id for "Show Vocab2". Term ids
 * aren't stable across environments/reruns, so the view can't hardcode them.
 * If terms from an earlier run of this script exist under their old names
 * ("Vocab1 A" / "Vocab1 B"), they're renamed in place rather than
 * duplicated.
 *
 * Maintenance note: this deliberately does NOT share code with
 * tests/src/Kernel/ViewsDependentFilterTest.php. That test builds throwaway
 * content in an isolated per-run test database; this script seeds durable,
 * human-visible content on the real dev site so you can click through the
 * exposed filters. But the two use the same story (a "Show Vocab2" term that
 * reveals the Vocab2 filter, and a "Hide Vocab2" term that doesn't), so if
 * you change what the plugin depends on, update the fixture data in both
 * places to keep them telling the same story.
 */
// phpcs:enable


use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Finds an existing taxonomy term by vocabulary + name, or creates it.
 *
 * @param string[] $legacy_names
 *   Former names for this term; if found under one of these, the term is
 *   renamed in place instead of a duplicate being created.
 */
function _vdf_demo_get_or_create_term(string $vid, string $name, array $legacy_names = []): Term {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $storage->loadByProperties(['vid' => $vid, 'name' => $name]);
  if ($term = reset($terms)) {
    return $term;
  }
  foreach ($legacy_names as $legacy_name) {
    $terms = $storage->loadByProperties(['vid' => $vid, 'name' => $legacy_name]);
    /** @var \Drupal\taxonomy\Entity\Term $term */
    if ($term = reset($terms)) {
      $term->setName($name)->save();
      print "Renamed term '{$legacy_name}' to '{$name}' ({$vid}, tid={$term->id()})\n";
      return $term;
    }
  }
  $term = Term::create(['vid' => $vid, 'name' => $name]);
  $term->save();
  print "Created term '{$name}' ({$vid}, tid={$term->id()})\n";
  return $term;
}

/**
 * Finds an existing vdf_test_cont node by title, or creates it.
 *
 * @param string[] $legacy_titles
 *   Former titles for this node; if found under one of these, the node is
 *   renamed in place instead of a duplicate being created.
 */
function _vdf_demo_get_or_create_node(string $title, ?Term $vocab1, ?Term $vocab2, array $legacy_titles = []): Node {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes = $storage->loadByProperties(['type' => 'vdf_test_cont', 'title' => $title]);
  if ($node = reset($nodes)) {
    return $node;
  }
  foreach ($legacy_titles as $legacy_title) {
    $nodes = $storage->loadByProperties(['type' => 'vdf_test_cont', 'title' => $legacy_title]);
    /** @var \Drupal\node\Entity\Node $node */
    if ($node = reset($nodes)) {
      $node->setTitle($title)->save();
      print "Renamed node '{$legacy_title}' to '{$title}' (nid={$node->id()})\n";
      return $node;
    }
  }
  $node = Node::create([
    'type' => 'vdf_test_cont',
    'title' => $title,
    'status' => 1,
    'field_vocab1' => $vocab1 ? [$vocab1->id()] : [],
    'field_vocab2' => $vocab2 ? [$vocab2->id()] : [],
  ]);
  $node->save();
  print "Created node '{$title}' (nid={$node->id()})\n";
  return $node;
}

// --- Terms -------------------------------------------------------------
// "Show Vocab2" is the controller value that reveals the Vocab2 filter;
// "Hide Vocab2" does not, so you can watch the dependent field hide/show as
// you switch between them in the exposed form.
$vocab1_show = _vdf_demo_get_or_create_term('vocab1', 'Show Vocab2', ['Vocab1 A']);
$vocab1_hide = _vdf_demo_get_or_create_term('vocab1', 'Hide Vocab2', ['Vocab1 B']);
$vocab2_term = _vdf_demo_get_or_create_term('vocab2', 'Vocab2 term');
$vocab2_term_b = _vdf_demo_get_or_create_term('vocab2', 'Vocab2 term B');

// --- Nodes ---------------------------------------------------------------
_vdf_demo_get_or_create_node('Matches both filters', $vocab1_show, $vocab2_term);
_vdf_demo_get_or_create_node('Matches only vocab1', $vocab1_show, NULL);
_vdf_demo_get_or_create_node('Matches neither', $vocab1_hide, NULL);
_vdf_demo_get_or_create_node('Show Vocab2 with second vocab2 term', $vocab1_show, $vocab2_term_b, ['Vocab1 A with second vocab2 term']);

// --- Wire up the view ------------------------------------------------------
$config = \Drupal::configFactory()->getEditable('views.view.vdf_test');
$key = 'display.default.display_options.filters.views_dependent_filter.controller_values';
$desired_controller_values = [(string) $vocab1_show->id() => (string) $vocab1_show->id()];
if ($config->get($key) !== $desired_controller_values) {
  $config->set($key, $desired_controller_values)->save();
  print "Set 'vdf_test' view's controller_values to trigger on 'Show Vocab2' (tid={$vocab1_show->id()})\n";
}

print "\nDone. The \"vdf_test\" view has no page display, so preview it at\n";
print "/admin/structure/views/view/vdf_test — pick \"Show Vocab2\" in the\n";
print "Vocab1 filter and the Vocab2 filter should appear; pick \"Hide Vocab2\"\n";
print "and it should stay hidden.\n";
