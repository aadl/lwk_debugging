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
    $db = \Drupal::database();

  // get work roles
    $work_role_options = [];
    $work_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name");
    foreach ($work_roles as $work_role) {
      $work_role_options[$work_role->wrid] = $work_role->name;
    }

    $form = [
      '#attributes' => ['class' => 'form-width-exception']
    ];
    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Add Creator'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      ];


    $form['collapsible']['table'] = [
      '#prefix' => '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
      '#suffix' => '</tr></table>',
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
    dblog('WorkAddArtistForm: buildForm: $current_path =', $current_path);

    $form['collapsible']['table']['role']['wrid'] = [
      '#type' => 'select',
      '#title' => 'Role',
      '#options' => $work_role_options,
      '#description' => '[' . ums_cardfile_create_link('Edit Creator Roles', 'cardfile/workroles', ['query' => ['return' => $current_path]]) . ']',
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
      '#autocomplete_route_name' => 'ums_cardfile.autocomplete',
      '#autocomplete_route_parameters' => [ 'type' => 'artist', 'name' => 'search_text'],
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
    $link = ums_cardfile_create_link('ADD NEW ARTIST', 'cardfile/artist/edit', ['query' => ['wid' => $wid]]);
    $form['collapsible']['table']['addNew'] = [
      '#suffix' => $link . '</p></td>'
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $clicked_element = $form_state->getTriggeringElement()['#id'];
    if ($clicked_element == 'edit-submit-recent') {
      $form_state->setRedirect('ums_cardfile.join', ['type1'            => 'work', 
                                                      'id1'             => $form_state->getValue('wid'),
                                                      'type2'           => 'artist', 
                                                      'id2'             => $form_state->getValue('recent_aid'),
                                                      'optional_key'    => 'wrid',
                                                      'optional_value'  => $form_state->getValue('wrid')
                                                    ]);
    } else {
      $form_state->setRedirect('ums_cardfile.searchadd', ['source_type'     => 'work',
                                                          'source_id'       => $form_state->getValue('wid'),
                                                          'type'            => 'artist',
                                                          'search'          => $form_state->getValue('search_text'),
                                                          'optional_key'    => 'wrid',
                                                          'optional_value'  => $form_state->getValue('wrid'),
                                                        ]);
    }
    return;
  }
}