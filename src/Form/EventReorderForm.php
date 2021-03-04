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

class EventReorderForm extends FormBase {
  public function getFormId() {
    return 'event_reorder_form';
  }

  // public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
  //   dblog('EventReorderForm buildForm ENTERED, eid =',$eid);
    
  //   $db = \Drupal::database();

  //   $form = [];
  //   $form['form_title'] = [
  //     '#value' => '<h1>' . t('Edit UMS Event') . '</h1>',
  //   ];

  //   if ($eid) {
  //     $event = _ums_cardfile_get_event($eid);
  //    dblog('EventReorderForm buildForm event = ', $event);
     
  //   $form['eid'] = [
  //       '#type' => 'value',
  //       '#value' => $eid,
  //     ];
  //   }
  //   dblog('EventReorderForm buildForm AFTER SETTING eid in form, $eid =', $form_state->getValue('eid'));

  //   $form['performances']['#tree'] = TRUE;

  //   foreach ($event['performances'] as $performance) {
  //     dblog('EventReorderForm buildForm foreach,   $ performance[pid] =', $performance['pid']);
  //     dblog('EventReorderForm buildForm foreach,  $performance[title] =', $performance['title']);
  //     dblog('EventReorderForm buildForm foreach, $performance[weight] =', $performance['weight']);
  //     $pid = $performance['pid'];
  //     $title = $performance['title'];
  //     $weight = $performance['weight'];
  //     $form['performances'][$pid] = [
  //       'title' => [
  //         '#value' => $title,
  //       ],
  //       'weight' => [
  //         '#type' => 'weight',
  //         '#title' => $title,
  //         '#delta' => 50,
  //         '#default_value' => $weight,
  //         '#attributes' => ['class' => 'weight'],
  //       ],
  //     ];
  //   }

  //   $form['submit'] = [
  //     '#prefix' => '<div class="container-inline">',
  //     '#type' => 'submit',
  //     '#value' => t('Save Order'),
  //     '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', 'cardfile/event/' . $event['eid']) . '</div>',
  //   ];

  //   return $form;
  // }

  // public function validateForm(array &$form, FormStateInterface $form_state) {
  // }

  // public function submitForm(array &$form, FormStateInterface $form_state) {
  //   dblog('EventReorderForm: submitForm: ENTERED');

  //   $db = \Drupal::database();
  //   $eid = $form_state->getValue('eid');

  //   foreach ($form_state->getValues['performances'] as $pid => $performance) {
  //     $weight = $performance['weight'] + 51; // #delta sets range from -50 to 50
  //     db_query("UPDATE ums_performances SET weight = %d WHERE pid = %d", $weight, $pid);
  //   }

  //   $form_state->setRedirect('ums_cardfile.event', ['eid' => $eid]);
  //   return;
  // }


  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
    dblog('EventReorderForm buildForm ENTERED, eid =',$eid);
    
    $db = \Drupal::database();
    $form = [
      '#attributes' => ['class' => 'form-width-exception']
    ];
    $form['#attached']['library'][] = 'ums_cardfile/cardfile-lib';
 
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Re-order UMS Event Performance') . '</h1>'
    ];
    $event = _ums_cardfile_get_event($eid);
    dblog('EventReorderForm buildForm event = ', $event);

    $form['eid'] = [
      '#type' => 'value',
      '#value' => $eid,
    ];
    
    dblog('EventReorderForm buildForm AFTER SETTING eid in form, $eid =', $form_state->getValue('eid'));

    $form['performances']['#tree'] = TRUE;

    $form['performances'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        //$this->t('Weight'),
      ],
      '#empty' => $this
        ->t('Sorry, There are no items!'),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    // Build the table rows and columns.
    //
    // The first nested level in the render array forms the table row, on which
    // you likely want to set #attributes and #weight.
    // Each child element on the second level represents a table column cell in
    // the respective table row, which are render elements on their own. For
    // single output elements, use the table cell itself for the render element.
    // If a cell should contain multiple elements, simply use nested sub-keys to
    // build the render element structure for the renderer service as you would
    // everywhere else.
    //
    // About the condition id<8:
    // For the purpose of this 'simple table' we are only using the first 8 rows
    // of the database.  The others are for 'nested' example.

    foreach ($event['performances'] as $performance) {
      $pid = $performance['pid'];
      $title = $performance['title'];
      $weight = $performance['weight'];
      dblog('EventReorderForm buildForm foreach,   pid =', $pid, 'title =', $title, 'weight =', $weight);

      // TableDrag: Mark the table row as draggable.
      $form['performances'][$pid]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      $form['performances'][$pid]['#weight'] = $weight;
      // Some table columns containing raw markup.
      $form['performances'][$pid]['title'] = [
        '#markup' => $title,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Save Order'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#attributes' => [
        'title' => $this
          ->t('Return to TableDrag Overview'),
      ],
      '#submit' => [
        '::cancel',
      ],
      '#limit_validation_errors' => [],
    ];
    
    return $form;
  }

  /**
   * Form submission handler for the simple form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db = \Drupal::database();
    $values = $form_state->getValues();
    dblog('values:', $values);
    foreach ($values['performances'] as $pid => $performance) {
      dblog('EventReorderForm submitForm - $pid =', $pid, 'performance=', $performance);
      $weight = $performance['weight'] + 51; // #delta sets range from -50 to 50
      $db->update('ums_performances')
        ->fields([
          'weight' => $weight,
        ])
        ->condition('pid', $pid, '=')
        ->execute();
    }
    $eid = $form_state->getValue('eid');

    $form_state->setRedirect('ums_cardfile.event', ['eid' => $eid]);
    return;



  }
}