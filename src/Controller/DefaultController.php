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

  /**
   * cf_artsts - handle artists display
   */
  public function cf_artists($filter = '') {
    $rows = [];
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

    dblog('cf_artists: ENTERED, count artists = ', count($artists));
    foreach ($artists as $artist) {
      if ($artist->photo_nid) {
        $photo_links = [];
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

  /**
   * cf_artist - handle artist edits
   */
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
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/artists');
      return new RedirectResponse($drupal_goto_url);
    }
  }

  /**
   * cf_delete_artist - handle venue deletion
   */
  public function cf_delete_artist($aid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_artists WHERE aid = :aid", [':aid' => $aid]);
    \Drupal::messenger()->addMessage('Removed the artist from the database');
    return [
    ];
  }

  /**
   * cf_venues - handle venues display
   */
  public function cf_venues() {
    $db = \Drupal::database();
    $venues = $db->query('SELECT * FROM ums_venues ORDER BY name')->fetchAll();
    $rows = [];
    foreach ($venues as $venue) {
       $rows[] = ['name' => $venue->name, 
                'id' => $venue->vid
              ];
    }
    dblog('cf_venues rows count =', count($rows));
    return [
        '#theme' => 'ums-cardfile-venues',
        '#venues' => $rows,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  
  /**
   * cf_venues - handle venue deletion
   */
  public function cf_delete_venue($vid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_venues WHERE vid = :vid", [':vid' => $vid]);
    \Drupal::messenger()->addMessage('Removed the venue from the database');
    return [
    ];
  }

  /**
   * cf_events - handle events display
   */
  public function cf_events($year = '') {
    dblog('cf_events  ENTERED year = ', $year);
    $db = \Drupal::database();
    $rows = [];

    if ($year) {
      if ($year == 'all') {
        $events = $db->query("SELECT * FROM ums_events WHERE 1 ORDER BY date")->fetchAll();
      } else {
        $events = $db->query("SELECT eid FROM ums_events WHERE YEAR(date) = :year ORDER BY date", [':year' => $year])->fetchAll();
      }
      // NOTE THIS IS SLOW WHEN 'ALL' IS reqyested - nearly 5000 records
      foreach($events as $e) {
        $event = _ums_cardfile_get_event($e->eid);
        if ($event['program_nid']) {
          $program_links = [];
          foreach (explode(',', $event['program_nid']) as $program_nid) {
            $program_nid = trim($program_nid);
            if (strpos($program_nid, '#') !== FALSE) {
              $parts = explode('#', $program_nid);
              $program_links[] = ums_cardfile_create_link('Program', 'node/' . $parts[0], ['fragment' => $parts[1]]);
            } else {
              $program_links[] = ums_cardfile_create_link('Program', 'node/' . $program_nid);
            }
          }
          $program_links = implode(', ', $program_links);
        } else {
          $program_links = '';
        }

        if ($event['photo_nid']) {
          $photo_links = [];
          foreach (explode(',', $event['photo_nid']) as $photo_nid) {
            $photo_nid = trim($photo_nid);
            $photo_links[] = ums_cardfile_create_link('Photo', 'node/' . $photo_nid);
          }
          $photo_links = implode(', ', $photo_links);
        } else {
          $photo_links = '';
        }
        $row = [
          'eid' => $event['eid'],
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
        ];
        $rows[] = $row;
      }
    }
    else {
      $event_years = $db->query('SELECT YEAR(date) AS event_year, COUNT(eid) AS event_count FROM ums_events GROUP BY event_year ORDER BY event_year')->fetchAll();
      foreach ($event_years as $e_year) {
        $row = [
          'year' => $e_year->event_year,
          'numberOfEvents' => $e_year->event_count,
        ];
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

  /**
   * cf_event - handle event editing
   */
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
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/events');
      return new RedirectResponse($drupal_goto_url);
    }
  }

  /**
   * cf_event - handle event deletion
   */
  public function cf_delete_event($eid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_events WHERE eid = :eid", [':eid' => $eid]);
    \Drupal::messenger()->addMessage('Removed the event from the database');
    return [
    ];
  }

  /**
   * cf_series - handle series display
   */
  public function cf_series() {
    $db = \Drupal::database();
    $series = $db->query('SELECT * FROM ums_series ORDER BY name')->fetchAll();
    $rows = [];
    foreach ($series as $aseries) {
       $rows[] = ['name' => $aseries->name, 
                'id' => $aseries->sid
              ];
    }
    dblog('cf_series rows count =', count($rows));

    $series_add_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\SeriesAddForm');

    return [
        '#theme' => 'ums-cardfile-series',
        '#series' => $rows,
        '#series_add_form' => $series_add_form,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  /**
   * cf_series - handle series deletion
   */
  public function cf_delete_series($sid) {  
    dblog('cf_delete_series sid =', $sid);
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_series WHERE sid = :sid", [':sid' => $sid]);
    \Drupal::messenger()->addMessage('Removed the series from the database');
    return [
    ];
  }

  /**
   * cf_work - handle works display
   */
  public function cf_works($filter = '') {
    $rows = [];
    $db = \Drupal::database();
    $page = pager_find_page();
 
    dblog('cf_works: ENTERED, $filter = ' . $filter . ', $page = ', $page);
    $per_page = 50;
    $offset = $per_page * $page;

    // if ($filter) {
    //   $result = pager_query('SELECT wid FROM ums_works WHERE title LIKE "%s%%" ORDER BY title', $per_page, 0, NULL, $filter);
    // } else {
    //   $result = pager_query('SELECT wid FROM ums_works ORDER BY title', $per_page);
    // }

    // $query = $db->select('ums_works', 'works')
    //   ->fields('works', ['aid','title','alternate','notes', 'youtube_url']);

    // if (NULL != $filter) {
    //   $query->condition('title', $db->escapeLike($filter) . "%", 'like');
    // }
    // $query->orderBy('title');

    $query = $db->select('ums_works', 'ums_works')
      ->fields('ums_works', ['wid']);

    if (NULL != $filter) {
      $query->condition('title', $db->escapeLike($filter) . "%", 'like');
    }
    $query->orderBy('title');

    $num_rows = $query->countQuery()->execute()->fetchField();
    $works = $query->range($offset, $per_page)->execute()->fetchAll();
    $pager = pager_default_initialize($num_rows, $per_page);

    dblog('cf_works: ENTERED, count works = ', count($works));
    $rows = [];
    foreach ($works as $work) {
      dblog("------------------------------------------------------- cf_works: $work->wid =", $work->wid);

      $work_details = _ums_cardfile_get_work($work->wid);
      if ($work_details) {
    // Format Creators
        $creators = '';
        dblog("cf_works: work_details count =", count($work_details['artists']));
        if (count($work_details['artists'])) {
          $creators .= '<span style="font-size: smaller">';
          foreach ($work_details['artists'] as $artist) {
            $link = ums_cardfile_create_link($artist['name'], 'cardfile/artist/' . $artist['aid']);
            $creators .= '<br />&nbsp;&bull; ' . $artist['role'] . ': ' . $link;
                          ;
          }
          $creators .= '</span>';
        }

        $row = [  
          'id' => $work_details['wid'],
          'creators' => '<strong>' . $work_details['title'] . '</strong>' . $creators,
          'alternate' => $work_details['alternate'],
          'notes' => strlen($work_details['notes']) > 30 ? substr($work_details['notes'], 0, 30) . '...' : $work_details['notes'],
        ];
        dblog('cf_works: row =', $row);
        $rows[] = $row;
      }
    }

    return [
      '#theme' => 'ums-cardfile-works',
      '#works' => $rows,
      '#filter' => $filter,
      '#pager' => [
        '#type' => 'pager',
        '#quantity' => 5
      ],
      '#cache' => [ 'max-age' => 0 ]
    ];
  }

  /**
   * cf_work - handle work edits
   */
  public function cf_work($wid = 0) {
    dblog('cf_work: ENTERED, $wid = ' . $wid);
    $db = \Drupal::database();

    $work = _ums_cardfile_get_work($wid);
    if ($work['wid']) {
      return [
        '#theme' => 'ums-cardfile-work',
        '#work' => $work,

        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      \Drupal::messenger()->addMessage("Unable to find work with ID:$wid");
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/works');
      return new RedirectResponse($drupal_goto_url);
    }
  }

  /**
   * cf_delete_work - handle work deletion
   */
  public function cf_delete_work($wid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_works WHERE wid = :wid", [':wid' => $wid]);
    \Drupal::messenger()->addMessage('Removed the work from the database');
    return [
    ];
  }

  

  public function cf_search_add($eid) {
  }



}