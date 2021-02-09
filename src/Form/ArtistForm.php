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

class ArtistForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $aid = 0) {
    dblog('ArtistForm buildForm ENTERED');
    
    $db = \Drupal::database();
    $form = [];
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Edit UMS artist') . '</h1>'
    ];
    if (isset($_GET['wid'])) {
      $work = _ums_cardfile_get_work(['wid']);
      $form['#prefix'] = '<p>Adding NEW Artist as a Creator of ' . $work['title'] . '</p>';
      $form['wid'] = [
        '#type' => 'value',
        '#value' => $work['wid'],
      ];
      // get work roles
      $work_role_options = [];
      $work_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name");
      foreach ($work_roles as $work_role) {
        $work_role_options[$work_role->wrid] = $work_role->name;
      }
      $form['wrid'] = [
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $work_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Creator Roles', 'cardfile/workroles', array('query' => array('return' => $_GET['q']))) . ']',
      ];
    } 
    elseif (isset($_GET['pid'])) {
      $performance = _ums_cardfile_get_performance($_GET['pid']);
      $form['#prefix'] = '<p>Adding NEW Artist as a Repertoire Performance Artist of ' . $performance['work']['title'] . '</p>';
      $form['pid'] = [
        '#type' => 'value',
        '#value' => $performance['pid'],
      ];
      // get performance roles
      $perf_role_options = [];
      $perf_roles = $db->query("SELECT * FROM ums_performance_roles ORDER BY name");
      foreach ($perf_roles as $perf_role) {
        $perf_role_options[$perf_role->prid] = $perf_role->name;
      }
      $form['prid'] = [
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $perf_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Artist Roles', 'cardfile/perfroles', array('query' => array('return' => $_GET['q']))) . ']',
      ];
    }

    $artist = ['aid' => '', 'name' => '', 'alias' => '', 'notes' => '', 'photo_nid' => ''];

    if ($aid) {
      $artist = _ums_cardfile_get_artist($aid);
      $form['aid'] = [
        '#type' => 'value',
        '#value' => $artist['aid'],
      ];
    }
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => (isset($_GET['name']) ? $_GET['name'] : $artist['name']),
      '#description' => $this->t('Name of Artist'),
    ];
    $form['alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alias'),
      '#size' => 64,
      '#maxlength' => 256,
      '#default_value' => $artist['alias'],
      '#description' => $this->t('Artist Aliases') . ' (' . t('separate multiple values with a comma') . ')',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#default_value' => $artist['notes'],
    ];
    $form['photo_nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photo ID'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $artist['photo_nid'],
      '#description' => $this->t('Node ID of the corresponding photo, separate multiple values with commas'),
    ];

    if ($artist['aid']) {
        // $form['merge_id'] = [
        //   '#type' => 'textfield',
        //   '#title' => $this->t('Merge this artist into Artist ID'),
        //   '#size' => 8,
        //   '#maxlength' => 8,
        //   '#description' => $this->t("Enter another Artist ID number to merge this artist information into that artist record"),
        //   '#prefix' => "<fieldset class=\"collapsible collapsed\"><legend>MERGE ARTIST</legend>",
        //   '#suffix' => "</fieldset>",
        // ];

      // $form['collapsible']['eid'] = [
      //   '#type' => 'value',
      //   '#value' => $eid,
      // ];

      $form['collapsible'] = [
        '#type' => 'details',
       '#title' => t('MERGE ARTIST'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      ];

      $form['collapsible']['merge_id'] = [
          '#type' => 'textfield',
          '#title' => t('Merge this artist into Artist ID'),
          '#description' => t('Enter another Artist ID number to merge this artist information into that artist record'),
          '#size' => 32,
          '#maxlength' => 32,
        ];
    }

    $form['submit'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => $this->t('Save Artist'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', 'cardfile/artists') . '</div>',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('submitForm: ENTERED');
    //Check for merge ID
    $values_array = $form_state->getValues();   //['collapsible']['merge_id']
    $merge_id = $values_array['merge_id'];
    $aid = $values_array['aid'];

    dblog('ArtistForm:: submitForm - merge_id ===>>> aid =',$merge_id, $aid);

    if ($form_state->getValue('aid') && $merge_id) {
      // if ($_REQUEST['destination']) {
      //   unset($_REQUEST['destination']);
      // }
      $form_state->setRedirect('ums_cardfile.artists.merge',
                                ['old_id' => $aid, //, ['aid' => $aid]);
                                 'merge_id' => $merge_id]); //, ['aid' => $aid]);
      return; 
    }

    $artist = [];
    $artist['name'] = $form_state['values']['name'];
    $artist['alias'] = $form_state['values']['alias'];
    $artist['notes'] = $form_state['values']['notes'];
    $artist['photo_nid'] = $form_state['values']['photo_nid'];

    // Convert Name to NamePlain for matching
    $artist['name_plain'] = ums_cardfile_normalize($artist['name']);

    $aid = $form_state->getValue('aid');
    dblog('EventForm: submitForm: aid=',$aid);
    if ($aid) {
      // update existing record
      $artist['aid'] = $aid;
      ums_cardfile_save('ums_artists', $artist, 'aid');
    } else {
      // new event
      ums_cardfile_save('ums_artists', $artist, NULL);
    }

    $db = \Drupal::database();
    $result = $db->query("SELECT aid FROM ums_artists ORDER BY aid desc limit 1")->fetch();
    $aid = $result->aid;

    if ($form_state->getValue('wid')) {
      // Create new work artist
     $form_state->setRedirect('ums_cardfile.join', 
                              ['type1' => 'work', 'id1' => $form_state->getValue('wid'), //, ['aid' => $aid]);
                                'type2' => 'artist', 'id2' => $aid]); //, ['aid' => $aid]);    
    } 
    elseif ($form_state->getValue('pid')) {
      // Create new work artist
      $form_state->setRedirect('ums_cardfile.join', 
                              ['type1' => 'performance', 'id1' => $form_state->getValue('pid'), //, ['aid' => $aid]);
                              'type2' => 'artist', 'id2' => $aid]); //, ['aid' => $aid]);    
    }
    else {
      drupal_set_message('Artist saved');
      //$form_state->setRedirect('ums_cardfile.artist', ['aid' => $aid]);
      $form_state->setRedirect('ums_cardfile.artist', ['aid' => $aid]); //, ['aid' => $aid]);
    }

    return;
  }
}