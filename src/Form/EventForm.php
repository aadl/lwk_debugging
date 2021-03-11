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

class EventForm extends FormBase {
  public function getFormId() {
    return 'event_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {    
    $db = \Drupal::database();

 // get venues
    $venue_options = [];
    $venues = $db->query("SELECT * FROM ums_venues ORDER BY name");
    foreach ($venues as $venue) {
      $venue_options[$venue->vid] = $venue->name;
    }
    // get series
    $series_options = [];
    $all_series = $db->query("SELECT * FROM ums_series ORDER BY name");
    foreach ($all_series as $series) {
      $series_options[$series->sid] = $series->name;
    }

    $event = [];
    $event['title'] = '';
    $event['date'] = '';
    $event['notes'] = '';
    $event['youtube_url'] = '';
    $event['program_nid'] = '';
    $event['photo_nid'] = '';
    $event['vid'] = 0;
    $event['sid'] = 0;


    $form = [];
    $form['form_title'] = [
      '#value' => '<h1>' . t('Edit UMS Event') . '</h1>',
    ];

    if ($eid) {
      $event = _ums_cardfile_get_event($eid);
      $form['eid'] = [
        '#type' => 'value',
        '#value' => $eid,
      ];
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Event Title'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $event['title'],
      '#description' => t('Title of event, if given'),
    ];
    $form['date'] = [
      '#type' => 'textfield',
      '#title' => t('Date'),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $event['date'],
      '#description' => t('Date of Event (YYYY-MM-DD [HH:MM:SS])') . '<br />' .
                        t('Event Time is optional, and should be in 24 hour format (e.g. 8:00 PM = 20:00:00)'),
    ];
    $form['venue'] = [
      '#type' => 'select',
      '#title' => t('Venue'),
      '#options' => $venue_options,
      '#default_value' => $event['vid'],
      '#description' => t('Location of Event') . ' [' . ums_cardfile_create_link('Edit Venue List', 'cardfile/venues') . ']',
    ];
    $form['series'] = [
      '#type' => 'select',
      '#title' => t('Series'),
      '#options' => $series_options,
      '#default_value' => $event['sid'],
      '#description' => t('Event Series') . ' [' . ums_cardfile_create_link('Edit Series List', 'cardfile/series') . ']',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => t('Notes'),
      '#default_value' => $event['notes'],
    ];
    $form['youtube_url'] = [
      '#type' => 'textarea',
      '#title' => t('YouTube URL(s)'),
      '#default_value' => $event['youtube_url'],
    ];
    $form['program_nid'] = [ 
      '#type' => 'textfield',
      '#title' => t('Program ID'),
      '#size' => 64,
      '#maxlength' => 64,
      '#default_value' => $event['program_nid'],
      '#description' => t('Node ID of the corresponding program, separate multiple values with commas'),
    ];
    $form['photo_nid'] = [
      '#type' => 'textfield',
      '#title' => t('Photo ID'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $event['photo_nid'],
      '#description' => t('Node ID of the corresponding photo, separate multiple values with commas'),
    ];
    $form['submit'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => t('Save Event'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', 'cardfile/events') . '</div>',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = [];
    $event['title'] = $form_state->getValue('title');
    $event['date'] = $form_state->getValue('date');
    $event['vid'] = $form_state->getValue('venue');
    $event['sid'] = $form_state->getValue('series');
    $event['notes'] = $form_state->getValue('notes');
    $event['youtube_url'] = $form_state->getValue('youtube_url');
    $event['program_nid'] = $form_state->getValue('program_nid');
    $event['photo_nid'] = $form_state->getValue('photo_nid');

    $eid = $form_state->getValue('eid');
    if ($eid) {
      // update existing record
      $event['eid'] = $eid;
      ums_cardfile_save('ums_events', $event, 'eid');
    } else {
      // new event
      ums_cardfile_save('ums_events', $event, NULL);

      $db = \Drupal::database();
      $result = $db->query("SELECT eid FROM ums_events ORDER BY eid desc limit 1")->fetch();
      $eid = $result->eid;
    }

    drupal_set_message('Event saved');
    $form_state->setRedirect('ums_cardfile.event', ['eid' => $eid]);
    return;
  }
}