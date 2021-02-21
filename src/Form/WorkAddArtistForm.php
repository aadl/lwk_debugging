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
    $db = \Drupal::database();

  // get work roles
    $work_role_options = [];
    $work_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name");
    foreach ($work_roles as $work_role) {
      $work_role_options[$work_role->wrid] = $work_role->name;
    }

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Add Creator'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
        '#prefix' => '<div id="LWK" style="width:1000px">',
        '#suffix' => '</div>'
      ];


    $form['collapsible']['table'] = [
      '#prefix' => '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
      '#suffix' => '</tr></table></fieldset>',
    ];
    $form['collapsible']['table']['wid'] = [
      '#type' => 'value',
      '#value' => $wid,
    ];
    $form['collapsible']['table']['role'] = [
      '#prefix' => '<td><div class="container-inline">',
      '#suffix' => '</div></td>',
    ];

    $current_path = \Drupal::service('path.current')->getPath();

    $form['collapsible']['table']['role']['wrid'] = [
      '#type' => 'select',
      '#title' => 'Role',
      '#options' => $work_role_options,
      '#description' => '[' .ums_cardfile_create_link('Edit Creator Roles', 'cardfile/workroles', ['query' => ['return' => $current_path]]) . ']',
    ];
    $form['collapsible']['table']['search'] = [
      '#prefix' => '<td><div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    ];
    $form['collapsible']['table']['search']['search_text'] = [
      '#type' => 'textfield',
      '#title' => t('Search for existing artist'),
      '#size' => 32,
      '#maxlength' => 32,
    ];
    $form['collapsible']['table']['search']['submit_search'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
    ];
    $form['collapsible']['table']['recent'] = [
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    ];
    $form['collapsible']['table']['recent']['recent_aid'] = [
      '#type' => 'select',
      '#title' => 'Recent artists',
      '#options' => ums_cardfile_recent_artists_d8(),
      '#description' => 'Select a recent artist',
    ];
    $form['collapsible']['table']['recent']['submit_recent'] = [
      '#type' => 'submit',
      '#value' => t('Use This Artist'),
    ];
    dblog('work_add_artist_form', ['query' => ['wid' => $wid]]);
    $form['collapsible']['table']['addNew'] = [
      '#suffix' => ums_cardfile_create_link('ADD NEW ARTIST', 'cardfile/artist/edit', ['query' => ['wid' => $wid]]) . '</p></td>'
    ];
    dblog('work_add_artist_form - RETURNING $form:', $form);
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('WorkAddArtistForm: submitForm ENTERED    NEEDS WORK TO FINISH THIS');
 
    if ($form_state['clicked_button']['#parents'][0] == 'submit_recent') {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/join/performance/' . $form_state['pid'] . '/artist/' . $form_state['recent_aid'],
                                                    ['prid' => $form_state['prid']]);
    } else {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/searchadd/performance/' . $form_state['pid'] . '/artist/' . $form_state['search_text'],
                                                    ['prid' => $form_state['prid']]);
    }
    dblog('WorkAddAtistForm:submitForm: $drupal_goto_url = [' . $drupal_goto_url . ']');
    return new RedirectResponse($drupal_goto_url);
  }
}