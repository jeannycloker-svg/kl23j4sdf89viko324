<?php

namespace Drupal\field_event_dispatcher\Event\Field;

/**
 * Class WidgetSingleElementTypeFormAlterEvent.
 *
 * @HookEvent(
 *   id = "widget_single_element_type_form_alter",
 *   alter = "field_widget_single_element_form"
 * )
 */
class WidgetSingleElementTypeFormAlterEvent extends WidgetSingleElementFormAlterEvent {

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $this->getContext()['items'];
    $fieldDefinition = $items->getFieldDefinition();
    return 'hook_event_dispatcher.widget_single_element_' . $fieldDefinition->getType() . '.alter';
  }

}
