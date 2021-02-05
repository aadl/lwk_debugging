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
    dblog('WorkForm buildForm ENTERED');
    
    $db = \Drupal::database();
    $form = [];
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Edit UMS Repertoire') . '</h1>'
    ];

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
  
    if ($form_state->getValue('aid') && $form_state->getValue('merge_id')) {
      if ($_REQUEST['destination']) {
        unset($_REQUEST['destination']);
      }
      $form_state->setRedirect('ums_cardfile.artists.merge',
                                ['oldid' => $form_state->getValue('aid'), //, ['aid' => $aid]);
                                 'mergeid' => $form_state->getValue('merge_id')]); //, ['aid' => $aid]);

      return; 
    }

    $artist = [
      'name'      => $form_state->getValue('name'),
      'alias'     => $form_state->getValue('alias'),
      'notes'     => $form_state->getValue('notes'),
      'photo_nid' => $form_state->getValue('photo_nid')
    ];
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