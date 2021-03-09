<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PerformanceAddArtistForm extends FormBase {
  public function getFormId() {
    return 'performance_add_artist_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0) {
    $db = \Drupal::database();

  // get work roles
    $perf_role_options = [];
    $performance_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name")->fetchAll();
    foreach ($performance_roles as $performance_role) {
      $perf_role_options[$performance_role->wrid] = $performance_role->name;
    }

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Add Repertoire Performance Artist'),
      //'#description' => t($desc_html),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#prefix' => '<div id="LWK" style="width:1000px">',
      '#suffix' => '</div>'
    ];
    $form['collapsible']['table'] = [
      '#prefix' => '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
      '#suffix' => '</tr></table>',
    ];

    $form['collapsible']['table']['pid'] = [
      '#type' => 'value',
      '#value' => $pid,
    ];

    $form['collapsible']['table']['role'] = [
      '#prefix' => '<td style="padding-right: 25px"><div class="container-inline">',
      '#suffix' => '</div></td>',
    ];

    $current_path = \Drupal::service('path.current')->getPath();

    $form['collapsible']['table']['role']['prid'] = [
      '#type' => 'select',
      '#title' => 'Role',
      '#options' => $perf_role_options,
      '#description' => '[' .ums_cardfile_create_link('Edit Artist Roles', 'cardfile/perfroles', ['query' => ['return' => $current_path]]) . ']',
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
    ];

    $form['collapsible']['table']['recent']['submit_recent'] = [
      '#type' => 'submit',
      '#value' => t('Use This Artist'),
    ];

    $form['collapsible']['table']['addNew'] = [
      '#suffix' => ums_cardfile_create_link('ADD NEW ARTIST', 'cardfile/artist/edit', ['query' => ['pid' => $pid]]) . '</p></td>'
    ];
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $clicked_element = $form_state->getTriggeringElement()['#id'];
    if ($clicked_element == 'edit-submit-recent') {
      $form_state->setRedirect('ums_cardfile.join', [ 'type1'           => 'performance',
                                                      'id1'             => $form_state->getValue('pid'),
                                                      'type2'           => 'artist',
                                                      'id2'             => $form_state->getValue('recent_aid'),
                                                      'optional_key'    => 'prid',
                                                      'optional_value'  => $form_state->getValue('prid'),
                                                    ]);
    } else {
      $form_state->setRedirect('ums_cardfile.searchadd', ['source_type'     => 'performance',
                                                          'source_id'       => $form_state->getValue('pid'),
                                                          'type'            => 'artist',
                                                          'search'          => $form_state->getValue('search_text'),
                                                          'optional_key'    => 'prid',
                                                          'optional_value'  => $form_state->getValue('prid'),
                                                        ]);
    }
    return;
  }
}