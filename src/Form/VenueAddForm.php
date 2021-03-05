<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class VenueAddForm extends FormBase {
  public function getFormId() {
    return 'venue_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $sid = 0) {
    dblog('VenueAddForm buildForm ENTERED');

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Add a Venue'),
      '#size' => 64,
      '#maxlength' => 128,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
    ];
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('VenueAddForm: ENTERED');
    
    $venue = [];
    $venue['name'] = $form_state->getValue('name');
    dblog('VenueForm::submitForm', $venue);
    ums_cardfile_save('ums_venues', $venue, NULL);
    drupal_set_message('Venue saved');
  }
}