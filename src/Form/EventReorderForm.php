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

  public function buildForm(array $form, FormStateInterface $form_state, $eid = 0) {
    $group_class = 'group-order-weight';

    $db = \Drupal::database();

    $form = [
      '#attributes' => ['class' => 'form-width-exception']
    ];
    $form['#attached']['library'][] = 'ums_cardfile/cardfile-lib';
 
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Re-order UMS Event Performance') . '</h1>'
    ];
    $event = _ums_cardfile_get_event($eid);

    $form['eid'] = [
      '#type' => 'value',
      '#value' => $eid
    ];

    // Build table.
    $form['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No items.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $group_class,
        ]
      ]
    ];

    // Build rows.
    foreach ($event['performances'] as $performance) {
      $key = $performance['pid'];
      $title = $performance['title'];
      $weight = $performance['weight'];
      $form['items'][$key]['#attributes']['class'][] = 'draggable';

      // Label col.
      $form['items'][$key]['title'] = [
        '#plain_text' => $title,
      ];

      // Weight col.
      $form['items'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => [$group_class]],
      ];
    }

    // Form action buttons.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#attributes' => [
        'title' => $this
          ->t('Cancel'),
      ],
      '#submit' => [
        '::cancel',
      ],
    ];
    dblog($form);
    return $form;
  }

  /**
   * Form submission handler for the 'Return to' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('ums_cardfile.event', ['eid' => $form_state->getValue('eid')]);
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
    $rows = $form_state->getValues();
    $row_number = 1;
    foreach ($rows['items'] as $pid => $item) {
      $weight = $row_number++;
      $db->update('ums_performances')
        ->fields(['weight' => $weight])
        ->condition('pid', $pid, '=')
        ->execute();
    }
    $form_state->setRedirect('ums_cardfile.event', ['eid' => $form_state->getValue('eid')]);
    
    return;
  }
}