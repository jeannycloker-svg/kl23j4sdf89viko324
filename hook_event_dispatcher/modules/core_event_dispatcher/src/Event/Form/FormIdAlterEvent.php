<?php

namespace Drupal\core_event_dispatcher\Event\Form;

/**
 * Class FormIdAlterEvent.
 *
 * @HookEvent(id="form_id_alter", alter="form")
 */
class FormIdAlterEvent extends AbstractFormEvent {

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    return 'hook_event_dispatcher.form_' . $this->getFormId() . '.alter';
  }

}
