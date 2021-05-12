<?php

/**
 * @file
 * Contains \Drupal\lwk_debugging\Form\ArborcatAdminForm.
 */

namespace Drupal\lwk_debugging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class Lwk_debuggingAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lwk_debugging_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lwk_debugging.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $config = \Drupal::config('lwk_debugging.settings');
    $form['max_num_chars'] = [
      '#type' => 'number',
      '#title' => t('Maximum # of characters'),
      '#default_value' => $config->get('max_num_chars'),
      '#description' => t('Maximum # of characters to be written when a large structure is passed in to be logged'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lwk_debugging.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
