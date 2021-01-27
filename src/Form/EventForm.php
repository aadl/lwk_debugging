<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class EventForm extends FormBase {
  public function getFormId() {
    return 'event_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
    dblog('EventForm buildForm ENTERED');
    
    $db = \Drupal::database();

 // get venues
    $venue_options = [];
    $venues = $db->query("SELECT * FROM ums_venues ORDER BY name");
    foreach $venue as $venues) {
      $venue_options[$venue->vid] = $venue->name;
    }
    // get series
    $series_options = [];
    $all_series = $db->query("SELECT * FROM ums_series ORDER BY name");
    foreach ($series as $all_series) {
      $series_options[$series->sid] = $series->name;
    }

    $form = [];
    $form['form_title'] = array(
      '#value' => '<h1>' . t('Edit UMS Event') . '</h1>',
    );
    if ($eid) {
      $event = _ums_cardfile_get_event($eid);
      $form['eid'] = array(
        '#type' => 'value',
        '#value' => $eid,
      );
    }
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Event Title'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $event['title'],
      '#description' => t('Title of event, if given'),
    );
    $form['date'] = array(
      '#type' => 'textfield',
      '#title' => t('Date'),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $event['date'],
      '#description' => t('Date of Event (YYYY-MM-DD [HH:MM:SS])') . '<br />' .
                        t('Event Time is optional, and should be in 24 hour format (e.g. 8:00 PM = 20:00:00)'),
    );
    $form['venue'] = array(
      '#type' => 'select',
      '#title' => t('Venue'),
      '#options' => $venue_options,
      '#default_value' => $event['vid'],
      '#description' => t('Location of Event') . ' [' . ums_cardfile_create_link('Edit Venue List', 'cardfile/venues') . ']',
    );
    $form['series'] = array(
      '#type' => 'select',
      '#title' => t('Series'),
      '#options' => $series_options,
      '#default_value' => $event['sid'],
      '#description' => t('Event Series') . ' [' . ums_cardfile_create_link('Edit Series List', 'cardfile/series') . ']',
    );
    $form['notes'] = array(
      '#type' => 'textarea',
      '#title' => t('Notes'),
      '#default_value' => $event['notes'],
    );
    $form['youtube_url'] = array(
      '#type' => 'textarea',
      '#title' => t('YouTube URL(s)'),
      '#default_value' => $event['youtube_url'],
    );
    $form['program_nid'] = array(
      '#type' => 'textfield',
      '#title' => t('Program ID'),
      '#size' => 64,
      '#maxlength' => 64,
      '#default_value' => $event['program_nid'],
      '#description' => t('Node ID of the corresponding program, separate multiple values with commas'),
    );
    $form['photo_nid'] = array(
      '#type' => 'textfield',
      '#title' => t('Photo ID'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $event['photo_nid'],
      '#description' => t('Node ID of the corresponding photo, separate multiple values with commas'),
    );
    $form['submit'] = array(
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => t('Save Event'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_linkl('Cancel', 'cardfile/events') . '</div>',
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('EventForm: submitForm: ENTERED');

    $event = new stdClass;
    $event->title = $form_state['values']['title'];
    $event->date = $form_state['values']['date'];
    $event->vid = $form_state['values']['venue'];
    $event->sid = $form_state['values']['series'];
    $event->notes = $form_state['values']['notes'];
    $event->youtube_url = $form_state['values']['youtube_url'];
    $event->program_nid = $form_state['values']['program_nid'];
    $event->photo_nid = $form_state['values']['photo_nid'];

    if ($form_state['values']['eid']) {
      // update existing record
      $event->eid = $form_state['values']['eid'];
      ums_cardfile_write_db_record('ums_events', $event, 'eid');
    } else {
      // new event
      ums_cardfile_write_db_record('ums_events', $event);
    }

    drupal_set_message('Event saved');
    $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/event/' . $event->eid);
    return new RedirectResponse($drupal_goto_url);

}