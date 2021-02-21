<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PerformanceCopyArtistForm extends FormBase {
  
  public function getFormId() {
    return 'performance_copy_artist_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0) {
    dblog('PerformanceCopyArtistForm: buildForm ENTERED - pid', $pid);
    $db = \Drupal::database();

    $performance = _ums_cardfile_get_performance($pid);

    dblog('PerformanceCopyArtistForm: performance: ', $performance);

    $form = array(
      '#prefix' => '<fieldset class="collapsible collapsed"><legend>Copy Performance Artists from other Repertoire</legend>',
      '#suffix' => '</tr></table></fieldset>',
    );
    $form['pid'] = array(
      '#type' => 'value',
      '#value' => $pid,
    );
    $other_pids = [];
    foreach ($performance['event']['performances'] as $other_performance) {
      if ($other_performance['pid'] != $pid) {
        $other_performance = _ums_cardfile_get_performance($other_performance['pid']);
        $description = '<strong>' . $other_performance['work']['title'] . '</strong>';
        foreach ($other_performance['artists'] as $artist) {
          $description .= '<br />' . $artist['name'] . ' (' . $artist['role'] . ')';
        }
        $other_pids[$other_performance['pid']] = $description;
      }
    }
    $form['source_pid'] = array(
      '#type' => 'radios',
      '#title' => 'Select Performance as source of Performance Artists',
      '#options' => $other_pids,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Copy Artists',
    );
      
    dblog('performance_add_artist_form - RETURNING' ); // $form:', $form);

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('PerformanceCopyArtistForm: submitForm ENTERED');
 
    $pid = $form_state['values']['pid'];
    $source_pid = $form_state['values']['source_pid'];

    // Copy all performance artists from source pid to new pid
    $res = db_query("SELECT * FROM ums_artist_performances WHERE pid = %d", $source_pid);
    while ($artist_perf = db_fetch_object($res)) {
      $artist_perf->pid = $pid;
      drupal_write_record('ums_artist_performances', $artist_perf);
    }
    drupal_set_message("Copied Repertoire Artists to the Performance");
  }
}