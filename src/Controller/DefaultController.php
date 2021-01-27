<?php /**
 * @file
 * Contains \Drupal\ums_cardfile\Controller\DefaultController.
 */

namespace Drupal\ums_cardfile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the ums_cardfile module.
 */
class DefaultController extends ControllerBase {
  public function cf_home() {
    return [
      '#theme' => 'ums-cardfile-home'
    ];
  }

  public function cf_artists($filter = '') {
    $rows = array();
    $db = \Drupal::database();
    $page = pager_find_page();
 
    dblog('cf_artists: ENTERED, $filter = ' . $filter . ', $page = ', $page);
    $per_page = 50;
    $offset = $per_page * $page;
    $query = $db->select('ums_artists', 'artists')
      ->fields('artists', ['aid','name','name_plain','alias','notes','photo_nid']);

    if (NULL != $filter) {
      $query->condition('name_plain', $db->escapeLike($filter) . "%", 'like');
    }
    $query->orderBy('name_plain');

    $num_rows = $query->countQuery()->execute()->fetchField();
    $artists = $query->range($offset, $per_page)->execute()->fetchAll();
    $pager = pager_default_initialize($num_rows, $per_page);

    foreach ($artists as $artist) {
      if ($artist->photo_nid) {
        $photo_links = array();
        foreach (explode(',', $artist->photo_nid) as $photo_nid) {
          $photo_nid = trim($photo_nid);
          $photo_links[] = ums_cardfile_create_link('Photo', 'node/' . $photo_nid);
        }
        $photo_links = implode(', ', $photo_links);
      } else {
        $photo_links = '';
      }
      $artist->photo_links = $photo_links;
    }

    return [
      '#theme' => 'ums-cardfile-artists',
      '#artists' => $artists,
      '#filter' => $filter,
      '#pager' => [
        '#type' => 'pager',
        '#quantity' => 5
      ],
      '#cache' => [ 'max-age' => 0 ]
    ];
  }

  public function cf_artist($aid = 0) {
    dblog('cf_artist: ENTERED, $aid = ' . $aid);
    $db = \Drupal::database();

    $artist = _ums_cardfile_get_artist($aid);
    if ($artist['aid']) {
      return [
        '#theme' => 'ums-cardfile-artist',
        '#artist' => $artist,

        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      \Drupal::messenger()->addMessage("Unable to find artist with ID:$aid");
      ums_cardfile_drupal_goto('cardfile/artists');
    }
  }

  public function cf_venues() {
    $db = \Drupal::database();
    $venues = $db->query('SELECT * FROM ums_venues ORDER BY name')->fetchAll();
    $rows = [];
    foreach ($venues as $venue) {
       $rows[] = ['name' => $venue->name, 
                'id' => $venue->vid
              ];
    }
    return [
        '#theme' => 'ums-cardfile-venues',
        '#rows' => $rows,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  public function cf_delete_venue($vid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_venues WHERE vid = :vid", [':vid' => $vid]);
    \Drupal::messenger()->addMessage('Removed the venue from the database');
    return [
    ];
  }

  public function cf_events($year = '') {
    dblog('cf_events  ENTERED year = ', $year);
    $db = \Drupal::database();

    if ($year) {
      if ($year == 'all') {
        $events = $db->query("SELECT * FROM ums_events WHERE 1 ORDER BY date")->fetchAll();
      } else {
        $events = $db->query("SELECT eid FROM ums_events WHERE YEAR(date) = :year ORDER BY date", [':year' => $year])->fetchAll();
      }
      foreach($events as $e) {
        $event = _ums_cardfile_get_event($e->eid);
        if ($event['program_nid']) {
          $program_links = array();
          foreach (explode(',', $event['program_nid']) as $program_nid) {
            $program_nid = trim($program_nid);
            if (strpos($program_nid, '#') !== FALSE) {
              $parts = explode('#', $program_nid);
              $program_links[] = ums_cardfile_create_link('Program', 'node/' . $parts[0], array('fragment' => $parts[1]));
            } else {
              $program_links[] = ums_cardfile_create_link('Program', 'node/' . $program_nid);
            }
          }
          $program_links = implode(', ', $program_links);
        } else {
          $program_links = '';
        }

        if ($event['photo_nid']) {
          $photo_links = array();
          foreach (explode(',', $event['photo_nid']) as $photo_nid) {
            $photo_nid = trim($photo_nid);
            $photo_links[] = ums_cardfile_create_link('Photo', 'node/' . $photo_nid);
          }
          $photo_links = implode(', ', $photo_links);
        } else {
          $photo_links = '';
        }
        $row = array('eid' => $event['eid'],
                    'date' => $event['date'],
                    'title' => $event['title'],
                    'venue' => $event['venue'],
                    'series' => $event['series'],
                    'notes' => strlen($event['notes']) > 30 ? substr($event['notes'], 0, 30) . '...' : $event['notes'],
                    'program_links' => $program_links,
                    'photo_links' => $photo_links,
                    'View' => ums_cardfile_create_link('VIEW', 'cardfile/event/' . $e->eid),
                    'Edit' => ums_cardfile_create_link('EDIT', 'cardfile/event/edit/' . $e->eid),
                    'Delete' => ums_cardfile_create_link('DELETE', 'cardfile/event/delete/' . $e->eid),
                  );
        $rows[] = $row;
      }
    }
    else {
      $rows = [];
      $event_years = $db->query('SELECT YEAR(date) AS event_year, COUNT(eid) AS event_count FROM ums_events GROUP BY event_year ORDER BY event_year')->fetchAll();
      foreach ($event_years as $e_year) {
        $row = array(
          'year' => $e_year->event_year,  // ums_cardfile_drupal_goto($e_year->event_year, 'cardfile/events/' . $e_year->event_year),
          'numberOfEvents' => $e_year->event_count,
        );
        $rows[] = $row;
        if (empty($header)) {
          $header = array_keys($row);
        }
      }
    }
    return [
        '#theme' => 'ums-cardfile-events',
        '#year' => $year,
        '#rows' => $rows,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  public function cf_event($eid = 0) {
    dblog('cf_event: ENTERED, $aid = ' . $eid);
    $db = \Drupal::database();

    $event = _ums_cardfile_get_event($eid);
    if ($event['eid']) {
      if (count($event['performances']) > 0) {
        $performance_rows = [];
        foreach($event['performances'] as $performance) {
          $performance_details = _ums_cardfile_get_performance($performance['pid']);

          $performance['event'] = $performance_details['event'];
          $performance['work'] = $performance_details['work'];
          $performance['artists'] = $performance_details['artists'];

          $work = $performance['work'];
          $performance['work_artists_list'] = '';

          if (count($work['artists'])) {
            $work_artists = [];
            foreach ($work['artists'] as $artist) {
              $work_artists[$artist['aid']]['name'] = $artist['name'];
              $work_artists[$artist['aid']]['roles'][] = $artist['role'];
            }
            $performance['work_artists_list'] = [];
            foreach ($work_artists as $work_artist) {
              $string = $work_artist['name'] . ' (' . implode(', ', $work_artist['roles']) . ')';
              array_push($performance['work_artists_list'], $string);
            }
          }

          if (count($performance['artists'])) {
            $performance_artists = [];
            foreach ($performance['artists'] as $artist) {
              $performance_artists[$artist['aid']]['name'] = $artist['name'];
              $performance_artists[$artist['aid']]['roles'][] = $artist['role'];
            }
            $performance['performance_artists_list'] = [];
            foreach ($performance_artists as $perf_artist) {
              $string = implode(', ', $perf_artist['roles']) .': ' . $perf_artist['name'];
              array_push($performance['performance_artists_list'], $string) ;
            }
          }
          $performance_rows[] = $performance;
        }
        $event['performances'] = $performance_rows;
        
      }

      return [
        '#theme' => 'ums-cardfile-event',
        '#event' => $event,
        '#event_add_performance_form' => \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\EventAddPerformanceForm'),
        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      \Drupal::messenger()->addMessage("Unable to find event with ID:$eid");
      ums_cardfile_drupal_goto('cardfile/events');
    }
  }

  public function cf_delete_event($eid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_events WHERE eid = :eid", [':eid' => $eid]);
    \Drupal::messenger()->addMessage('Removed the event from the database');
    return [
    ];
  }

}