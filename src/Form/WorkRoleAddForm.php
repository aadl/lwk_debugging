<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\WorkRoleAddForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class WorkRoleAddForm extends FormBase {
  public function getFormId() {
    return 'work_role_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $return = '') {
    dblog('WorkRoleAddForm buildForm ENTERED');

    $form = [
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
    ];
    if ($return) {
      $form['return'] = [
        '#type' => 'value',
        '#value' => $return,
      ];
    }
    $form['name'] = [
        '#type' => 'textfield',
        '#title' => t('Add a Creator Role'),
        '#size' => 64,
        '#maxlength' => 128,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => ($return ? t('Add and Return to Previous Page') : t('Add')),
    ];
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('WorkRoleAddForm: submitForm ENTERED');
    $new_role = [];
    $new_role['name'] = $form_state->getValue('name');
    ums_cardfile_save('ums_work_roles', $new_role, NULL);
    drupal_set_message('Added new Creator Role');

    if ($return = $form_state->getValue('return')) {
      $drupal_goto_url = ums_cardfile_drupal_goto($return);
      dblog('WorkRoleAddForm: submitForm $return = ', $return, ', drupal_goto_url = ', $drupal_goto_url);
      return new RedirectResponse($drupal_goto_url);
    }
  }
}