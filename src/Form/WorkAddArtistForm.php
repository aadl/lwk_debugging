<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WorkAddArtistForm extends FormBase {
  public function getFormId() {
    return 'work_add_artist_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $wid = 0) {
    dblog('WorkAddArtistForm: buildForm ENTERED - WHAT IS wid', $wid);

  // get work roles
    $work_role_options = array();
    $res = db_query("SELECT * FROM ums_work_roles ORDER BY name");
    while ($work_role = db_fetch_object($res)) {
      $work_role_options[$work_role->wrid] = $work_role->name;
    }

    $form = array(
      '#prefix' => '<fieldset class="collapsible collapsed"><legend>Add Creator</legend>' .
                  '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
      '#suffix' => '</tr></table></fieldset>',
    );
    $form['wid'] = array(
      '#type' => 'value',
      '#value' => $wid,
    );
    $form['role'] = array(
      '#prefix' => '<td><div class="container-inline">',
      '#suffix' => '</div></td>',
    );
    $form['role']['wrid'] = array(
      '#type' => 'select',
      '#title' => 'Role',
      '#options' => $work_role_options,
      '#description' => '[' . l('Edit Creator Roles', 'cardfile/workroles', array('query' => array('return' => $_GET['q']))) . ']',
    );
    $form['search'] = array(
      '#prefix' => '<td><div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    );
    $form['search']['search_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Search for existing artist'),
      '#size' => 32,
      '#maxlength' => 32,
    );
    $form['search']['submit_search'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );
    $form['recent'] = array(
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    );
    $form['recent']['recent_aid'] = array(
      '#type' => 'select',
      '#title' => 'Recent artists',
      '#options' => ums_cardfile_recent_artists(),
      '#description' => 'Select a recent artist',
    );
    $form['recent']['submit_recent'] = array(
      '#type' => 'submit',
      '#value' => t('Use This Artist'),
    );
    $form['addNew'] = array(
      '#value' => l('ADD NEW ARTIST', 'cardfile/artist/edit', array('query' => array('wid' => $wid))) . '</p></td>',
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('WorkAddArtistForm: submitForm ENTERED');
 
    if ($form_state['clicked_button']['#parents'][0] == 'submit_recent') {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/join/work/' . $form_state['values']['wid'] . '/artist/' . $form_state['values']['recent_aid'],
                                                    array('wrid' => $form_state['values']['wrid']));
    } else {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/searchadd/work/' . $form_state['values']['wid'] . '/artist/' . $form_state['values']['search_text'],
                                                    array('wrid' => $form_state['values']['wrid']));
    }
    return new RedirectResponse($drupal_goto_url);
  }
}