<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\PerfRoleAddForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class PerfRoleAddForm extends FormBase {
  public function getFormId() {
    return 'venue_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $return = '') {
    dblog('PerfRoleAddForm buildForm ENTERED');

  $form = array(
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  if ($return) {
    $form['return'] = array(
      '#type' => 'value',
      '#value' => $return,
    );
  }
  $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Add a Artist Role'),
      '#size' => 64,
      '#maxlength' => 128,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => ($return ? t('Add and Return to Previous Page') : t('Add')),
    );
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('PerfRoleAddForm: ENTERED');
    $new_role = [];
    $new_role['name'] = $form_state->getValue('name');
    ums_cardfile_save('ums_performance_roles', $new_role, NULL);
    drupal_set_message('Added new role');

    if ($return = $form_state->getValue('return')) {
      $drupal_goto_url = ums_cardfile_drupal_goto($return);
      return new RedirectResponse($drupal_goto_url);
    }
  }
}