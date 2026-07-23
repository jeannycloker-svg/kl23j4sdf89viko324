<?php

namespace Drupal\ui_patterns_extends;

use Drupal\Component\Graph\Graph;
use Drupal\ui_patterns\Definition\PatternDefinition;

/**
 * Extends base pattern from pattern.
 */
class UIPatternsExtends {

  /**
   * The pattern definition.
   *
   * @var \Drupal\ui_patterns\Definition\PatternDefinition
   */
  private $definition;

  /**
   * List of all pattern definitions.
   *
   * @var \Drupal\ui_patterns\Definition\PatternDefinition[]
   */
  private $definitions;

  /**
   * List of previous names.
   *
   * @var string[]
   */
  private $previousPatternNames;

  /**
   * UIPatternsExtender constructor.
   */
  public function __construct(PatternDefinition $definition, array $definitions, $previous_pattern_names = []) {
    $this->definition = $definition;
    $this->definitions = $definitions;
    $this->previousPatternNames = $previous_pattern_names;
    $definition_id = $definition->id();
    if (in_array($definition_id, $previous_pattern_names)) {
      throw new \Exception("Pattern recursion found. $definition_id found in hierarchy. " . implode($previous_pattern_names));
    }
  }

  /**
   * Extends pattern definition.
   */
  public function extends() {
    $additional = $this->definition->getAdditional();
    if (isset($additional['extends']) &&
      is_array($additional['extends'])) {

      $extends = $additional['extends'];
      foreach ($extends as $extend) {
        [$pattern, $pattern_part, $part_name] = array_pad(explode('.', $extend), 3, NULL);
        if (isset($this->definitions['yaml:' . $pattern])) {
          if ($pattern_part === NULL) {
            $lookups = ['fields', 'settings', 'variants'];
          }
          else {
            $lookups = [$pattern_part];
          }
          $this->previousPatternNames[] = $pattern;
          if (!isset($this->definitions['yaml:' . $pattern])) {
            throw new \Exception("Parent pattern lookup $pattern failed.");
          }
          $from_definition = $this->definitions['yaml:' . $pattern];
          $from_additional = $from_definition->getAdditional();
          if (isset($from_additional['extends']) &&
            is_array($from_additional['extends'])) {
            $extender = new UIPatternsExtends($this->definitions['yaml:' . $pattern], $this->definitions, $this->previousPatternNames);
            $from_definition = $extender->extends();
            $from_additional = $from_definition->getAdditional();
          }

          foreach ($lookups as $lookup) {
            switch ($lookup) {
              case 'fields':
                $from_fields = $from_definition->getFields();
                foreach ($from_fields as $name => $from_field) {
                  if ($this->definition->hasField($name) === FALSE && ($part_name === NULL || $part_name === $name)) {
                    $this->definition->setFields([$name => $from_field->toArray()]);
                  }
                }
                break;

              case 'settings':
                if (isset($from_additional['settings'])) {
                  foreach ($from_additional['settings'] as $setting_name => $setting) {
                    if (isset($additional['settings'][$setting_name]) === FALSE
                      && ($part_name === NULL || $part_name === $setting_name)
                    ) {
                      $additional['settings'][$setting_name] = $from_additional['settings'][$setting_name];
                    }
                  }
                }
                break;
            }
          }
        }
      }
    }
    unset($additional['extends']);
    $this->definition->setAdditional($additional);
  }

}
