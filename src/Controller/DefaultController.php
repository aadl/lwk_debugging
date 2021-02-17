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

  public function cf_artists_merge($old_id, $merge_id, $confirm = '') {
    if ($confirm) {
      _ums_cardfile_merge_artist($old_id, $merge_id);
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/artist' . $merge_id);
      return new RedirectResponse($drupal_goto_url);
    }

    $old_artist = _ums_cardfile_get_artist($old_id);
    $artist = _ums_cardfile_get_artist($merge_id);

    dblog('ISSET: old_artist->performances :', isset($old_artist['performances']));
    dblog('ISSET: old_artist->works        :', isset($old_artist['works']));
    dblog('ISSET: artist->performances     :', isset($artist['performances']));
    dblog('ISSET: artist->works            :', isset($artist['works']));

    if ($old_artist['aid'] && $artist['aid']) {
      $old_artist['performances'] = (isset($old_artist['performances'])) ? count($old_artist['performances']) : 0;
      $old_artist['works'] = (isset($old_artist['works'])) ? count($old_artist['works']) : 0;

      $artist['performances'] = (isset($artist['performances'])) ? count($artist['performances']) : 0;
      $artist['works'] = (isset($artist['works'])) ? count($artist['works']) : 0;
 
      $merge_table = [];
      foreach ($old_artist as $field => $old_artist_data) {
        $arrows = (!empty($old_artist_data) && empty($artist[$field]) ? '>>>>' : '');
        $merge_table[] = ["$field", $old_artist_data, $arrows, $artist[$field]];
      }

      return [
        '#theme' => 'ums-cardfile-merge-artists',
        '#merge_data' => $merge_table,
        '#cache' => [ 'max-age' => 0 ]
      ];

    } else {
      drupal_set_message('Invalid Artist IDs', 'error');
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/artists');
      return new RedirectResponse($drupal_goto_url);
    }
    return [];
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

    $venue_add_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\VenueAddForm');

    return [
        '#theme' => 'ums-cardfile-venues',
        '#venues' => $rows,
        '#venue_add_form' => $venue_add_form,
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
     dblog('cf_events year NULL');
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
        '#event_add_performance_form' => \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\EventAddPerformanceForm', $event['eid']),
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
    dblog('cf_works ENTERED filter =', $filter);
    $rows = [];
    $db = \Drupal::database();
    $page = pager_find_page();
 
    dblog('cf_works: ENTERED, $filter = ' . $filter . ', $page = ', $page);
    $per_page = 50;
    $offset = $per_page * $page;

    $query = $db->select('ums_works', 'ums_works')
      ->fields('ums_works', ['wid']);

    if (NULL != $filter) {
      dblog('filter not null - ', $filter);
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
          'wid' => $work_details['wid'],
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
    dblog('cf_work: ENTERED, $work = ',$work);
    if ($work['wid']) {
      $work_add_artist_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\WorkAddArtistForm', $work['wid']);
      return [
        '#theme' => 'ums-cardfile-work',
        '#work' => $work,
        '#work_add_artist_form' => $work_add_artist_form,
        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      \Drupal::messenger()->addMessage("Repertoire not found",'error', TRUE);
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

  public function cf_join($type1, $id1, $type2, $id2) {
    dblog("cf_join: ENTERED type1 = $type1, id1 = $id1, type2 = $type2, id2 = $id2");
    $db = \Drupal::database();
    if ($type1 == 'event' && $type2 == 'source_event') {
      // Copy all performances from source_event to event
      $res = $db->query("SELECT * FROM ums_performances WHERE eid = id2", [':id2' => $id2])->fetchAll();
      while ($copy_perf = db_fetch_object($res)) {
        $copy_pid = $copy_perf->pid;
        unset($copy_perf->pid);
        $copy_perf->eid = $id1;
        drupal_write_record('ums_performances', $copy_perf);
        $res2 = $db->query("SELECT * FROM ums_artist_performances WHERE pid = %d", $copy_pid)->fetchAll();
        while ($copy_artist_perf = db_fetch_object($res2)) {
          $copy_artist_perf->pid = $copy_perf->pid;
          drupal_write_record('ums_artist_performances', $copy_artist_perf);
        }
      }
      drupal_set_message("All Performances copied from event $id2 to event $id1");
      drupal_goto('cardfile/event/' . $id1);
    }
    if ($type1 == 'event' && $type2 == 'work') {
      // New Performance
      $max = db_fetch_object(db_query("SELECT MAX(weight) as max_weight FROM ums_performances WHERE eid = %d", $id1));
      $perf = new stdClass;
      $perf->eid = $id1;
      $perf->wid = $id2;
      $perf->weight = $max->max_weight + 1;
      drupal_write_record('ums_performances', $perf);
      drupal_set_message('Created new Repertoire Performance for Event ID: ' . $id1 .
                        '<br />Add Artist Info below:');
      drupal_goto('cardfile/performance/' . $perf->pid);
    } elseif ($type1 == 'performance' && $type2 == 'artist') {
      $artist_perf = new stdClass;
      $artist_perf->pid = $id1;
      $artist_perf->aid = $id2;
      $artist_perf->prid = $_GET['prid'];
      drupal_write_record('ums_artist_performances', $artist_perf);
      drupal_set_message("Added new Repertoire Artist to the Performance");
      ums_cardfile_recent_artists_d8($id2);
      drupal_goto('cardfile/performance/' . $artist_perf->pid);
    } elseif ($type1 == 'work' && $type2 == 'artist') {
      $artist_work = new stdClass;
      $artist_work->wid = $id1;
      $artist_work->aid = $id2;
      $artist_work->wrid = $_GET['wrid'];
      drupal_write_record('ums_artist_works', $artist_work);
      drupal_set_message("Added new Creator to the Repertoire");
      ums_cardfile_recent_artists($id2);
      drupal_goto('cardfile/work/' . $artist_work->wid);
    }
  }

  public function cf_searchadd($source_type, $source_id, $type, $search) {
    dblog("cf_searchadd: ENTERED source_type = $source_type, source_id = $source_id, type = $type, search = $search");
    $db = \Drupal::database();
    if ($source_type == 'event') {
      $event = _ums_cardfile_get_event($source_id);
      $heading_text = 'repertoire to event: ' . $event['date'] . ' at ' . $event['venue'];
    } 
    elseif ($source_type == 'performance') {
      $performance = _ums_cardfile_get_performance($source_id);
      $prid = \Drupal::request()->query->get('prid');
      dblog('cf_searchadd: prid =', $prid);
      $performance_role = $db->query('SELECT * FROM ums_performance_roles WHERE prid = :prid', [':prid' => $prid])->fetchAll();
      dblog('cf_searchadd: performance_role =', $performance_role);
      $query_args = ['prid' => $performance_role->prid];
      dblog('cf_searchadd: query_args =', $query_args);
      $heading_text = '<strong>' . $performance_role->name . '</strong> to ' . $performance['work']['title'] . ' at event: ' .
                            $performance['event']['date'] . ' at ' . $performance['event']['venue'];
    } 
    elseif ($source_type == 'work') {
      $work = _ums_cardfile_get_work($source_id);
      $wrid = \Drupal::request()->query->get('wrid');
      dblog('cf_searchadd: wrid =', $wrid);
      $performance_role = $db->query('SELECT * FROM ums_work_roles WHERE wrid = :wrid', [':wrid' => $wrid])->fetchAll();
      dblog('cf_searchadd: performance_role =', $performance_role);      
      $query_args = ['wrid' => $performance_role->wrid];
      dblog('cf_searchadd: query_args =', $query_args);
      $heading_text = '<strong>' . $performance_role->name . '</strong> to ' . $work['title'];
    }

    return [
      '#theme' => 'ums-cardfile-searchadd',
      '#heading_text' => $heading_text,
      '#cache' => [ 'max-age' => 0 ]
    ];
    $output = [];

    // Special Handling for copying performances from one event to another
    if (preg_match('/event:([\d]+)/', $search, $matches)) {
      $source_eid = $matches[1];
      $source_event = _ums_cardfile_get_event($source_eid);
      $output .= '<p>Copy Repertoire Performances from Event ' . $source_event['date'] . ' at ' . $source_event['venue'] . '?</p>';
      $output .= '<ul>';
      foreach ($source_event['performances'] as $source_perf) {
        $output .= '<li>' . $source_perf['title'] . '</li>';
      }
      $output .= '</ul><p>' . l('COPY PERFORMANCES TO THIS EVENT', "cardfile/join/$source_type/$source_id/source_event/$source_eid") . '</p>';
    } else {
      // Split search string into keywords
      $search_terms = explode(' ', str_replace(',', '', $search));
      $output .= '<p>Searching for "' . implode('" AND "', $search_terms) . '"</p>';
      $search_query_parts = array();
      $search_args = array();

      if ($type == 'work') {
        $wids = array();

        foreach ($search_terms as $search_term) {
          $search_query_part = "(ums_works.title LIKE '%%%s%%'";
          $works_search_args[] = $search_term;
          $artists_search_args[] = $search_term;
          $search_query_part .= " OR ums_works.alternate LIKE '%%%s%%'";
          $works_search_args[] = $search_term;
          $artists_search_args[] = $search_term;
          $search_query_part .= " OR ums_works.notes LIKE '%%%s%%'";
          $works_search_args[] = $search_term;
          $artists_search_args[] = $search_term;

          $works_query_parts[] = $search_query_part . ')';

          $search_query_part .= " OR ums_artists.name LIKE '%%%s%%'";
          $artists_search_args[] = $search_term;
          $search_query_part .= " OR ums_artists.name_plain LIKE '%%%s%%'";
          $artists_search_args[] = $search_term;
          $search_query_part .= " OR ums_artists.alias LIKE '%%%s%%'";
          $artists_search_args[] = $search_term;
          $search_query_part .= " OR ums_artists.notes LIKE '%%%s%%')";
          $artists_search_args[] = $search_term;

          $artists_query_parts[] = $search_query_part;
        }

        $res = db_query(
          "SELECT wid " .
                        "FROM ums_works " .
                        "WHERE " .
                        implode(' AND ', $works_query_parts) .
                        " ORDER BY wid",
          $works_search_args
        );
        while ($match = db_fetch_object($res)) {
          $wids[$match->wid] = $match->wid;
        }

        $res = db_query(
          "SELECT ums_works.wid AS wid " .
                        "FROM ums_works, ums_artist_works, ums_artists " .
                        "WHERE ums_works.wid = ums_artist_works.wid " .
                        "AND ums_artist_works.aid = ums_artists.aid " .
                        "AND " . implode(' AND ', $artists_query_parts) .
                        " ORDER BY wid",
          $artists_search_args
        );
        while ($match = db_fetch_object($res)) {
          $wids[$match->wid] = $match->wid;
        }

        if (count($wids)) {
          $works = array();
          $res = db_query("SELECT ums_works.wid as wid, " .
                          "ums_works.title as title, " .
                          "ums_works.alternate as alternate, " .
                          "ums_works.notes as notes, " .
                          "ums_works.youtube_url as youtube_url," .
                          "ums_artists.name as artist_name, " .
                          "ums_work_roles.name AS role " .
                          "FROM ums_works " .
                          "LEFT JOIN ums_artist_works ON ums_works.wid = ums_artist_works.wid " .
                          "LEFT JOIN ums_work_roles ON ums_artist_works.wrid = ums_work_roles.wrid " .
                          "LEFT JOIN ums_artists ON ums_artist_works.aid = ums_artists.aid " .
                          "WHERE ums_works.wid IN (" .
                          implode(',', $wids) .
                          ") ORDER BY ums_works.title");

          while ($match = db_fetch_object($res)) {
            if ($works[$match->wid]) {
              // Work data already captured, just add artist info
              $works[$match->wid]['Artists'] .= "<br /><strong>$match->role:</strong> $match->artist_name";
            } else {
              $works[$match->wid] = array(
                'Work ID' => $match->wid,
                'Title' => $match->title,
                'Alternate' => $match->alternate,
                'Artists' => "<strong>$match->role:</strong> $match->artist_name",
                'Notes' => $match->notes,
                'SELECT' => l('SELECT', "cardfile/join/$source_type/$source_id/work/" . $match->wid),
              );
            }
          }

          $output .= theme('table', array_keys(reset($works)), $works);
          $output .= '<p><strong>- OR -</strong></p>';
        } else {
          $output .= '<p>No existing repertoire matches found</p>';
        }

        $output .= '<p>' . l('ADD NEW REPERTOIRE', 'cardfile/work/edit', array('query' => array('eid' => $source_id, 'title' => $search))) . '</p>';
      } elseif ($type == 'artist') {
        foreach ($search_terms as $search_term) {
          $search_query_part = "(name LIKE '%%%s%%'";
          $search_args[] = $search_term;
          $search_query_part .= " OR name_plain LIKE '%%%s%%'";
          $search_args[] = $search_term;
          $search_query_part .= " OR alias LIKE '%%%s%%'";
          $search_args[] = $search_term;
          $search_query_part .= " OR notes LIKE '%%%s%%')";
          $search_args[] = $search_term;
          $search_query_parts[] = $search_query_part;
        }
        $res = db_query(
          "SELECT * FROM ums_artists " .
                        "WHERE " .
                        implode(' AND ', $search_query_parts) .
                        "ORDER BY name ASC",
          $search_args
        );

        $rows = array();
        while ($artist = db_fetch_array($res)) {
          $rows[] = array(
            'Artist ID' => $artist['aid'],
            'Name' => $artist['name'],
            'Alias' => $artist['alias'],
            'Notes' => $artist['notes'],
            'SELECT' => l(
              'SELECT',
              "cardfile/join/$source_type/$source_id/artist/" . $artist['aid'],
              array('query' => $query_args)
            ),
          );
        }
        $output .= theme('table', array_keys($rows[0]), $rows);
        $output .= '<p><strong>- OR -</strong></p>';
        if ($source_type == 'performance') {
          $source_id_name = 'pid';
        } elseif ($source_type == 'work') {
          $source_id_name = 'wid';
        }
        $output .= '<p>' . l('ADD NEW ARTIST', 'cardfile/artist/edit', array('query' => array($source_id_name => $source_id, 'name' => $search))) . '</p>';
      }
    }

      return [
        '#theme' => 'ums-cardfile-searchadd',
        '#heading_text' => $heading_text,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }


}