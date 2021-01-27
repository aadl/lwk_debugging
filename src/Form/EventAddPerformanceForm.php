<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class EventAddPerformanceForm extends FormBase {
  public function getFormId() {
    return 'event_add_performance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
    dblog('EventAddPerformanceForm buildForm ENTERED');

    $form = array(
      '#prefix' => '<fieldset class="collapsible collapsed"><legend>Add Repertoire Performance *** LWK *** WORK NEEDED HERE</legend><div class="container-inline">',
      '#suffix' => '</div></fieldset>',
    );
    $form['eid'] = array(
      '#type' => 'value',
      '#value' => $eid,
    );
    $form['search'] = array(
      '#type' => 'textfield',
      '#title' => t('Search for existing repertoire'),
      '#size' => 32,
      '#maxlength' => 32,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );
    $form['addNew'] = array(
      '#type' => 'button',
      '#prefix' => '<strong> - OR - </strong>',
     '#value' => ums_cardfile_create_link('ADD NEW REPERTOIRE', 'cardfile/work/edit', array('query' => array('eid' => $eid))),
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('EventAddPerformanceForm: ENTERED');
    
    ums_cardfile_drupal_goto('cardfile/searchadd/event/' . $form_state['values']['eid'] . '/work/' . $form_state['values']['search']);
  }
}