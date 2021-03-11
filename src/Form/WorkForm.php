<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class WorkForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $wid = 0) {
    $db = \Drupal::database();

    $cancel_path = 'cardfile/works';
    $form = [];
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Edit UMS Repertoire') . '</h1>'
    ];
    // Get optional parameters from URL
    $url_eid = \Drupal::request()->query->get('eid');
    $url_title = \Drupal::request()->query->get('title');

    if ($url_eid) {
      $event = _ums_cardfile_get_event($url_eid);
      $form['#prefix'] = '<p>Adding NEW Repertoire to event: ' . $event['date'] . ' at ' . $event['venue'] . '</p>';
      $form['eid'] = [
        '#type' => 'value',
        '#value' => $event['eid'],
      ];
    }
    if ($wid) {
      $work = _ums_cardfile_get_work($wid);
      $form['wid'] = [
        '#type' => 'value',
        '#value' => $wid,
      ];
      $cancel_path = 'cardfile/work/' . $work['wid'];
    }
    $work_title = (isset($work)) ? $work['title'] : '';
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => ($url_title) ? $url_title : $work_title,
      '#description' => t('Title of Repertoire'),
    ];
    $form['alternate'] = [
      '#type' => 'textfield',
      '#title' => t('Alternate Title'),
      '#size' => 64,
      '#maxlength' => 256,
      '#default_value' => (isset($work)) ? $work['alternate'] : '',
      '#description' => t('Alternate Titles for the Repertoire') . ' (' . t('separate multiple values with a comma') . ')',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => t('Notes'),
      '#default_value' => (isset($work)) ? $work['notes'] : '',
    ];

    if (isset($work) && $work['wid']) {
      $form['collapsible'] = [
        '#type' => 'details',
       '#title' => t('MERGE REPERTOIRE'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      ];

      $form['collapsible']['merge_id'] = [
          '#type' => 'textfield',
          '#title' => t('Merge this repertoire into Repertoire ID'),
          '#description' => t('Enter another Repertoire ID number to merge this repertoire information into that repertoire record'),
          '#size' => 8,
          '#maxlength' => 8,
        ];
    }

    $form['submit'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => $this->t('Save Repertoire'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', $cancel_path) . '</div>',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Check for merge ID
    if ($form_state->getValue('aid') && $form_state->getValue('merge_id')) {
      // if ($_REQUEST['destination']) {
      //   unset($_REQUEST['destination']);
      // }
      $form_state->setRedirect(
        'ums_cardfile.works.merge',
        ['old_id' => $form_state->getValue('aid'), 'merge_id' => $form_state->getValue('merge_id')]
      );

      return;
    }

    $work = [
      'title'      => $form_state->getValue('title'),
      'alternate'  => $form_state->getValue('alternate'),
      'notes'      => $form_state->getValue('notes'),
    ];

    $wid = $form_state->getValue('wid');
    if ($wid) {
      // update existing record
      $work['wid'] = $wid;
      ums_cardfile_save('ums_works', $work, 'wid');
    } else {
      // new work
      $new_wid = ums_cardfile_save('ums_works', $work, NULL);
      $work['wid'] = $new_wid;
    }

    $eid = $form_state->getValue('eid');
    if ($eid) {
      $form_state->setRedirect('ums_cardfile.join',
                                [ 'type1' => 'event', 
                                  'id1' => $form_state->getValue('eid'),
                                  'type2' => 'work', 
                                  'id2' => $work['wid']
                                ]);
    }
    else {
      $form_state->setRedirect('ums_cardfile.work', ['wid' => $wid]);
    }
    drupal_set_message('Repertoire saved');

    return;
  }
}