<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EventAddPerformanceForm extends FormBase {
  public function getFormId() {
    return 'event_add_performance_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
    dblog('EventAddPerformanceForm buildForm ENTERED');

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Add Repertoire Performance'),
      //'#description' => t($desc_html),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#attributes' => [
        'style' => 'width: 400px;'
      ],
    ];
    $form['collapsible']['eid'] = [
      '#type' => 'value',
      '#value' => $eid,
    ];

    $form['collapsible']['search'] = [
      '#type' => 'textfield',
      '#title' => t('Search for existing repertoire'),
      '#size' => 150,
      '#maxlength' => 150,
    ];
    $form['collapsible']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];
    $form['collapsible']['addNew'] = [
      '#type' => 'button',
      '#prefix' => '<strong> - OR - </strong>',
     '#value' => ums_cardfile_create_link('ADD NEW REPERTOIRE', 'cardfile/work/edit', ['query' => ['eid' => $eid]]),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('EventAddPerformanceForm: submitForm ENTERED');               
    $form_state->setRedirect('ums_cardfile.searchadd', [ 'source_type' => 'event',
                                                          'source_id'  => $form_state->getValue('eid'),
                                                          'type'      => 'work',
                                                          'search'     => $form_state->getValue('search')
                                                        ]);
    return;
  }
}