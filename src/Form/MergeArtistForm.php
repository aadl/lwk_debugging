<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MergeArtistForm extends FormBase {
  public function getFormId() {
    return 'event_add_performance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $aid = 0) {
    dblog('MergeArtistForm buildForm ENTERED');

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('MERGE ARTIST'),
      //'#description' => t($desc_html),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    ];

    $form['collapsible']['search'] = array(
      '#type' => 'textfield',
      '#title' => t('Merge this artist into Artist ID'),
      '#description' => t('Enter another Artist ID number to merge this artist information into that artist record'),
      '#size' => 32,
      '#maxlength' => 32,
    );
    $form['collapsible']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('MergeArtistForm: ENTERED');
    
    $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/searchadd/event/' . $form_state['values']['eid'] . '/work/' . $form_state['values']['search']);
    return new RedirectResponse($drupal_goto_url);
  }
}