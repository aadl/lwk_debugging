<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class VenueForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $aid = 0) {
    dblog('ArtistForm buildForm ENTERED');
    
    $db = \Drupal::database();
    $form = [];
    $form['title'] = array(
      '#markup' => '<h1>' . $this->t('Edit UMS artist') . '</h1>',
    );
    if (isset($_GET['wid'])) {
      $work = _ums_cardfile_get_work(['wid']);
      $form['#prefix'] = '<p>Adding NEW Artist as a Creator of ' . $work['title'] . '</p>';
      $form['wid'] = array(
      '#type' => 'value',
      '#value' => $work['wid'],
      );
      // get work roles
      $work_role_options = array();
      $work_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name");
      foreach ($work_roles as $work_role) {
        $work_role_options[$work_role->wrid] = $work_role->name;
      }
      $form['wrid'] = array(
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $work_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Creator Roles', 'cardfile/workroles', array('query' => array('return' => $_GET['q']))) . ']',
      );
    } 
    elseif (isset($_GET['pid'])) {
      $performance = _ums_cardfile_get_performance($_GET['pid']);
      $form['#prefix'] = '<p>Adding NEW Artist as a Repertoire Performance Artist of ' . $performance['work']['title'] . '</p>';
      $form['pid'] = array(
      '#type' => 'value',
      '#value' => $performance['pid'],
    );
      // get performance roles
      $perf_role_options = array();
      $perf_roleS = $db->query("SELECT * FROM ums_performance_roles ORDER BY name");
      foreach ($perf_roleS as $perf_role) {
        $perf_role_options[$perf_role->prid] = $perf_role->name;
      }
      $form['prid'] = array(
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $perf_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Artist Roles', 'cardfile/perfroles', array('query' => array('return' => $_GET['q']))) . ']',
      );
    }

    $artist = ['aid' => '', 'name' => '', 'alias' => '', 'notes' => '', 'photo_nid' => ''];

    if ($aid) {
      $artist = _ums_cardfile_get_artist($aid);
        $form['aid'] = array(
        '#type' => 'value',
        '#value' => $artist['aid'],
      );
    }
    $form['name'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Name'),
    '#size' => 64,
    '#maxlength' => 128,
    '#default_value' => (isset($_GET['name']) ? $_GET['name'] : $artist['name']),
    '#description' => $this->t('Name of Artist'),
  );
    $form['alias'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Alias'),
    '#size' => 64,
    '#maxlength' => 256,
    '#default_value' => $artist['alias'],
    '#description' => $this->t('Artist Aliases') . ' (' . t('separate multiple values with a comma') . ')',
  );
    $form['notes'] = array(
    '#type' => 'textarea',
    '#title' => $this->t('Notes'),
    '#default_value' => $artist['notes'],
  );
    $form['photo_nid'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Photo ID'),
    '#size' => 64,
    '#maxlength' => 128,
    '#default_value' => $artist['photo_nid'],
    '#description' => $this->t('Node ID of the corresponding photo, separate multiple values with commas'),
  );

    if ($artist['aid']) {
      $form['merge_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merge this artist into Artist ID'),
      '#size' => 8,
      '#maxlength' => 8,
      '#description' => $this->t("Enter another Artist ID number to merge this artist information into that artist record"),
      '#prefix' => "<fieldset class=\"collapsible collapsed\"><legend>MERGE ARTIST</legend>",
      '#suffix' => "</fieldset>",
      );
    }

    $form['submit'] = array(
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => $this->t('Save Artist'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', 'cardfile/artists') . '</div>',
    );

    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('submitForm: ENTERED');
    // Check for merge ID
    
    if ($form_state->getValue('aid') && $form_state->getValue('merge_id')) {
      if ($_REQUEST['destination']) {
        unset($_REQUEST['destination']);
      }
      ums_cardfile_drupal_goto('cardfile/artists/merge/' . $form_state->getValue('aid') . '/' . $form_state->getValue('merge_id'));
    }

    $artist = [];
    $artist->name = $form_state->getValue('name');
    $artist->alias = $form_state->getValue('alias');
    $artist->notes = $form_state->getValue('notes');
    $artist->photo_nid = $form_state->getValue('photo_nid');

    // Convert Name to NamePlain for matching
    $artist->name_plain = ums_cardfile_normalize($artist->name);

    if ($form_state->getValue('aid')) {
      // update existing record
      $artist->aid = $form_state->getValue('aid');
      ums_cardfile_write_db_record('ums_artists', $artist, ['aid' => $artist->aid] );
    } else {
      // new artist
      ums_cardfile_write_db_record('ums_artists', $artist, ['aid' => null]);
    }

    if ($form_state->getValue('wid')) {
      // Create new work artist
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/join/work/' . $form_state->getValue('wid') . '/artist/' . $artist->aid,
        array('wrid' => $form_state->getValue('wrid')));
    } 
    elseif ($form_state->getValue('pid')) {
      // Create new work artist
      $url = ums_cardfile_drupal_goto(
        'cardfile/join/performance/' . $form_state->getValue('pid') . '/artist/' . $artist->aid,
        array('prid' => $form_state->getValue('prid')));
    }
    else {
     drupal_set_message('Artist saved');
     $drupal_goto_string = ums_cardfile_drupal_goto('cardfile/artist/' . $artist->aid);
    }
    return new RedirectResponse($drupal_goto_url);
  }
}