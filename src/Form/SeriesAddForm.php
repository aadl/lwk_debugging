<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SeriesAddForm extends FormBase {
  public function getFormId() {
    return 'series_add_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $sid = 0) {
    dblog('SeriesAddForm buildForm ENTERED');

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Add a series'),
      '#size' => 32,
      '#maxlength' => 32,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
    );
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('SeriesAddForm: ENTERED');
    
    $series = [];
    $series['name'] = $form_state->getValue('name');

    ums_cardfile_save('ums_series', $series, NULL);
    drupal_set_message('Series saved');




  }
}