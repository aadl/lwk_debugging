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

 // ===============================================================================================
 // ===============================================================================================

  /**
   * cf_artsts - handle artists display
   */
  public function cf_artists($filter = '') {
    $rows = [];
    $db = \Drupal::database();
    $page = pager_find_page();
 
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
      return new RedirectResponse('/cardfile/artists');
    }
  }

  /**
   * cf_delete_artist - handle venue deletion
   */
  public function cf_delete_artist($aid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_artists WHERE aid = :aid", [':aid' => $aid]);
    \Drupal::messenger()->addMessage('Removed the artist from the database');

    return new RedirectResponse('/cardfile/artists');
  }

  public function cf_artists_merge($old_id, $merge_id, $confirm = '') {
    if ($confirm) {
      _ums_cardfile_merge_artist($old_id, $merge_id);
      return new RedirectResponse('/cardfile/artist/' . $merge_id);
    }

    $old_artist = _ums_cardfile_get_artist($old_id);
    $artist = _ums_cardfile_get_artist($merge_id);

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
      return new RedirectResponse('/cardfile/artists');
    }
    return [];
  }

 // ===============================================================================================
 // ===============================================================================================

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
    
    return new RedirectResponse('/cardfile/venues');
  }

 // ===============================================================================================
 // ===============================================================================================

  /**
   * cf_events - handle events display
   */
  public function cf_events($year = '') {
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

          if (is_array($work) && count($work['artists'])) {
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
      return new RedirectResponse('/cardfile/events');
    }
  }

  /**
   * cf_event - handle event deletion
   */
  public function cf_delete_event($eid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_events WHERE eid = :eid", [':eid' => $eid]);
    \Drupal::messenger()->addMessage('Removed the event from the database');
    return new RedirectResponse('/cardfile/events');
  }

 // ===============================================================================================
 // ===============================================================================================

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
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_series WHERE sid = :sid", [':sid' => $sid]);
    \Drupal::messenger()->addMessage('Removed the series from the database');

    return new RedirectResponse('/cardfile/series');
  }

 // ===============================================================================================
 // ===============================================================================================
 /**
   * cf_work - handle works display
   */
  public function cf_works($filter = '') {
    $rows = [];
    $db = \Drupal::database();
    $page = pager_find_page();
    $per_page = 50;
    $offset = $per_page * $page;

    $query = $db->select('ums_works', 'ums_works')
      ->fields('ums_works', ['wid']);

    if (NULL != $filter) {
      $query->condition('title', $db->escapeLike($filter) . "%", 'like');
    }
    $query->orderBy('title');

    $num_rows = $query->countQuery()->execute()->fetchField();
    $works = $query->range($offset, $per_page)->execute()->fetchAll();
    $pager = pager_default_initialize($num_rows, $per_page);

    $rows = [];
    foreach ($works as $work) {
      $work_details = _ums_cardfile_get_work($work->wid);
      if ($work_details) {
    // Format Creators
        $creators = '';
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
    $db = \Drupal::database();

    $work = _ums_cardfile_get_work($wid);    
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
      return new RedirectResponse('/cardfile/works');
    }
  }

  /**
   * cf_delete_work - handle work deletion
   */
  public function cf_delete_work($wid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_works WHERE wid = :wid", [':wid' => $wid]);
    \Drupal::messenger()->addMessage('Removed the work from the database');
    
    return new RedirectResponse('/cardfile/works');
  }

  public function cf_works_merge($old_wid, $merge_id, $confirm = '') {
    if ($confirm) {
      _ums_cardfile_merge_work($old_wid, $merge_id);
      return new RedirectResponse('/cardfile/work/' . $merge_id);
    }

    $old_work = _ums_cardfile_get_artist($old_wid);
    $work = _ums_cardfile_get_artist($merge_id);

    if ($old_work['wid'] && $work['wid']) {
      $old_work['artists'] = (isset($old_work['artists'])) ? count($old_work['artists']) : 0;
      $old_work['events'] = (isset($old_work['events'])) ? count($old_work['events']) : 0;

      $work['artists'] = (isset($work['artists'])) ? count($work['artists']) : 0;
      $work['events'] = (isset($work['events'])) ? count($work['events']) : 0;
 
      $merge_table = [];
      foreach ($old_work as $field => $old_work_data) {
        $arrows = (!empty($old_work_data) && empty($work[$field]) ? '>>>>' : '');
        $merge_table[] = ["$field", $old_work_data, $arrows, $work[$field]];
      }

      return [
        '#theme' => 'ums-cardfile-merge-works',
        '#merge_data' => $merge_table,
        '#cache' => [ 'max-age' => 0 ]
      ];

    } else {
      drupal_set_message('Invalid Repertoire IDs', 'error');
      return new RedirectResponse('/cardfile/works');
    }
    return [];
  }

   /**
   * cf_work_delete_artist - handle work artist deletion
   */
  public function cf_work_delete_artist($aid, $wid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_artist_works WHERE aid = :aid AND wid = :wid", [':aid' => $aid, ':wid' => $wid]);
    \Drupal::messenger()->addMessage('Artist has been deleted');
    
    return new RedirectResponse('/cardfile/work/' . $wid);
  }


// ===============================================================================================
// ===============================================================================================
  /**
   * cf_performance - handle performance edits
   */
  public function cf_performance($pid = 0) {
    $db = \Drupal::database();

    $performance = _ums_cardfile_get_performance($pid);    
    if ($performance['pid']) {
      $work_add_artist_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\WorkAddArtistForm', $performance['work']['wid']);
      $performance_add_artist_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\PerformanceAddArtistForm', $performance['pid']);
      $performance_copy_artists_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\PerformanceCopyArtistsForm', $performance['pid']);
      return [
        '#theme' => 'ums-cardfile-performance',
        '#performance' => $performance,
        '#work_add_artist_form' => $work_add_artist_form,
        '#performance_add_artist_form' => $performance_add_artist_form,
        '#performance_copy_artists_form' => $performance_copy_artists_form,
        '#cache' => [ 'max-age' => 0 ]
      ];
    } else {
      \Drupal::messenger()->addMessage("Performance not found",'error', TRUE);
      return new RedirectResponse('/cardfile/events');
    }
  }

  /**
   * cf_delete_performance - handle performance deletion
   */
  public function cf_delete_performance($pid) {  
    $performance = _ums_cardfile_get_performance($pid);
    $eid = $performance['eid'];

    $db = \Drupal::database();
    $db->query("DELETE FROM ums_artist_performances WHERE pid = :pid", [':pid' => $pid]);
    $db->query("DELETE FROM ums_performances WHERE pid = :pid", [':pid' => $pid]);
    \Drupal::messenger()->addMessage('Repertoire performance has been deleted');
    return new RedirectResponse('/cardfile/event/' . $eid);
  }

   /**
   * cf_performance_delete_artist - handle performance artist deletion
   */
  public function cf_performance_delete_artist($aid, $pid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_artist_performances WHERE aid = :aid AND pid = :pid", [':aid' => $aid, ':pid' => $pid]);
    \Drupal::messenger()->addMessage('Artist has been deleted');

    return new RedirectResponse('/cardfile/performance/' . $pid);
  }
  
 // ===============================================================================================
 // ===============================================================================================

  /**
   * cf_perfroles - handle Performance Roles display
   */
  public function cf_perfroles() {
    $db = \Drupal::database();
    $rows = $db->query('SELECT * FROM ums_performance_roles ORDER BY name')->fetchAll();
    $perf_roles = json_decode(json_encode($rows), TRUE);
    $return_param = \Drupal::request()->query->get('return');

    $perfrole_add_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\PerfRoleAddForm', $return_param);
    return [
        '#theme' => 'ums-cardfile-perfroles',
        '#perfroles' => $perf_roles,
        '#perf_role_add_form' => $perfrole_add_form,
        '#return' => $return_param,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  /**
   * cf_delete_perfrole - handle Performance Role deletion
   */
  public function cf_delete_perfrole($prid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_performance_roles WHERE prid = :prid", [':prid' => $prid]);
    \Drupal::messenger()->addMessage('Removed Artist Role from database');
    return new RedirectResponse('/cardfile/perfroles');
  }

 // ===============================================================================================
 // ===============================================================================================

  /**
   * cf_workroles - handle Work Roles display
   */
  public function cf_workroles() {
    $db = \Drupal::database();
    $rows = $db->query('SELECT * FROM ums_work_roles ORDER BY name')->fetchAll();
    $work_roles = json_decode(json_encode($rows), TRUE);
    $return_param = \Drupal::request()->query->get('return');
    $workrole_add_form = \Drupal::formBuilder()->getForm('Drupal\ums_cardfile\Form\WorkRoleAddForm', $return_param);
    return [
        '#theme' => 'ums-cardfile-workroles',
        '#workroles' => $work_roles,
        '#work_role_add_form' => $workrole_add_form,
        '#return' => $return_param,
        '#cache' => [ 'max-age' => 0 ]
      ];
  }

  /**
   * cf_delete_workrole - handle Performance Role deletion
   */
  public function cf_delete_workrole($wrid) {  
    $db = \Drupal::database();
    $db->query("DELETE FROM ums_work_roles WHERE wrid = :wrid", [':wrid' => $wrid]);
    \Drupal::messenger()->addMessage('Removed Creator Role from database');
    return new RedirectResponse('/cardfile/workroles');
  }

// ===============================================================================================
// ===============================================================================================

  public function cf_join($type1, $id1, $type2, $id2, $optional_key='', $optional_value='') {
    $redirectlink = '/cardfile';
    $db = \Drupal::database();
    if ($type1 == 'event' && $type2 == 'source_event') {
      // Copy all performances from source_event to event
      $performances = $db->query("SELECT * FROM ums_performances WHERE eid = :id2", [':id2' => $id2])->fetchAll(PDO::FETCH_ASSOC);
      foreach ($performances as $copy_perf) {
        $copy_pid = $copy_perf->pid;
        unset($copy_perf->pid);
        $copy_perf->eid = $id1;
        ums_cardfile_save('ums_performances', $copy_perf, NULL);
        $artist_performances = $db->query("SELECT * FROM ums_artist_performances WHERE pid = :copy_pid", [':copy_pid' => $copy_pid])->fetchAll();

        foreach($artist_performances as $copy_artist_perf) {
          $copy_artist_perf->pid = $copy_perf->pid;
          ums_cardfile_save('ums_artist_performances', $copy_artist_perf, NULL);
        }
      }
      drupal_set_message("All Performances copied from event $id2 to event $id1");
      $redirectlink = '/cardfile/event/' . $id1;
   }
    if ($type1 == 'event' && $type2 == 'work') {
      // New Performance
      $max = $db->query("SELECT MAX(weight) as max_weight FROM ums_performances WHERE eid = :id1", [':id1' => $id1])->fetchAssoc();
      $perf = [];
      $perf['eid'] = $id1;
      $perf['wid']  = $id2;
      $perf['weight'] = $max['max_weight'] + 1;
      $pid = ums_cardfile_save('ums_performances', $perf, NULL);
      drupal_set_message('Created new Repertoire Performance for Event ID: ' . $id1 .
                        '. Add Artist Info below:');
      $redirectlink = '/cardfile/performance/' . $pid;
    } 
    elseif ($type1 == 'performance' && $type2 == 'artist') {
      $artist_perf = [];
      $artist_perf['pid'] = $id1;
      $artist_perf['aid'] = $id2;
      $artist_perf['prid'] = $optional_value;
      ums_cardfile_save('ums_artist_performances', $artist_perf, NULL);
      drupal_set_message("Added new Repertoire Artist to the Performance");
      ums_cardfile_recent_artists_d8($id2);
      $redirectlink = '/cardfile/performance/' . $artist_perf['pid'];
    } 
    elseif ($type1 == 'work' && $type2 == 'artist') {
      $artist_work = [];
      $artist_work['wid'] = $id1;
      $artist_work['aid'] = $id2;
      $artist_work['wrid'] = $optional_value;
      ums_cardfile_save('ums_artist_works', $artist_work, NULL);
      drupal_set_message("Added new Creator to the Repertoire");
      ums_cardfile_recent_artists_d8($id2);
      $redirectlink = '/cardfile/work/' . $artist_work['wid'];
   }
    return new RedirectResponse($redirectlink);
  }

  public function cf_searchadd($source_type, $source_id, $type, $search, $optional_key='', $optional_value='') {
    $db = \Drupal::database();

    $search_terms_list = [];
    $works = [];
    $artists = [];

    if ($source_type == 'event') {
      $event = _ums_cardfile_get_event($source_id);
      $heading_text = 'Adding repertoire to event: ' . $event['date'] . ' at ' . $event['venue'];
    } 
    elseif ($source_type == 'performance') {
     $performance = _ums_cardfile_get_performance($source_id);
      $prid = $optional_value;
      $performance_role = $db->query("SELECT * FROM ums_performance_roles WHERE prid = :prid", [':prid' => $prid])->fetchAssoc();
      $query_args = ['prid' => $performance_role['prid']];
      $heading_text = 'Adding a <strong>' . $performance_role['name'] . '</strong> to ' . $performance['work']['title'] . ' at event: ' .
                            $performance['event']['date'] . ' at ' . $performance['event']['venue'];
    } 
    elseif ($source_type == 'work') {
      $work = _ums_cardfile_get_work($source_id);
      $wrid = $optional_value;
      $work_role = $db->query("SELECT * FROM ums_work_roles WHERE wrid = :wrid", [':wrid' => $wrid])->fetchAssoc();
      $query_args = ['wrid' => $work_role['wrid']];
      $heading_text = 'Adding a <strong>' . $work_role['name'] . '</strong> to ' . $work['title'];
    }

    $copy_performance_flag = FALSE;
    $source_eid = 0;
    $source_event = [];
    // -------------------------------------------------------------------
    // Special Handling for copying performances from one event to another
    // -------------------------------------------------------------------
    if (preg_match('/event:([\d]+)/', $search, $matches)){
      $copy_performance_flag = TRUE;
      $source_eid = $matches[1];
      $source_event = _ums_cardfile_get_event($source_eid);
    } 
    // -------------------------------------------------------------------
    // Search using 'search' terms passed in
    // -------------------------------------------------------------------
    else {
      // Split search string into keywords
      $search_terms = explode(' ', str_replace(',', '', $search));
      $search_terms_list = implode('" AND "', $search_terms);
      // $output .= '<p>Searching for "' . implode('" AND "', $search_terms) . '"</p>';
      $search_query_parts = [];
      $search_args = [];
          
      if ($type == 'work') {    // ----------------------- WORK
        $wids = [];
        $works_query_parts = [];
        $artists_query_parts = [];
        $search_args = [];
        $search_terms_iterator = 0;
        foreach ($search_terms as $search_term) {
          $search_term_placeholder = ':search_term_' . $search_terms_iterator++;
          $search_query_part = "(ums_works.title LIKE $search_term_placeholder";
          $search_query_part .= " OR ums_works.alternate LIKE $search_term_placeholder";
          $search_query_part .= " OR ums_works.notes LIKE $search_term_placeholder";
          $works_query_parts[] = $search_query_part . ')';

          $search_query_part .= " OR ums_artists.name LIKE $search_term_placeholder";
          $search_query_part .= " OR ums_artists.name_plain LIKE $search_term_placeholder";
          $search_query_part .= " OR ums_artists.alias LIKE $search_term_placeholder";
          $search_query_part .= " OR ums_artists.notes LIKE $search_term_placeholder)";
          $artists_query_parts[] = $search_query_part;
          $search_args[$search_term_placeholder] = '%%' . $search_term . '%%';
        }

        $select_statement = "SELECT wid FROM ums_works WHERE " . implode(' AND ', $works_query_parts) . " ORDER BY wid";
        $res = $db->query($select_statement, $search_args)->fetchAll();
        foreach($res as $match) {
          $wids[$match->wid] = $match->wid;
        }

        $select_statement = "SELECT ums_works.wid AS wid " .
                        "FROM ums_works, ums_artist_works, ums_artists " .
                        "WHERE ums_works.wid = ums_artist_works.wid " .
                        "AND ums_artist_works.aid = ums_artists.aid " .
                        "AND " . implode(' AND ', $artists_query_parts) .
                        " ORDER BY wid";
        $res = $db->query($select_statement, $search_args)->fetchAll();
        foreach( $res as $match) {
          $wids[$match->wid] = $match->wid;
        }

        if (count($wids)) {
          $works = [];
          $res = $db->query("SELECT ums_works.wid as wid, " .
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
                          ") ORDER BY ums_works.title")->fetchAll();

          foreach( $res as $match) {
            if (array_key_exists($match->wid, $works)) {
             // Work data already captured, just add artist info
              $works[$match->wid]['Artists'] .= "<br /><strong>" . $match->role . ':</strong> ' . $match->artist_name;
            } else {
            $select_link = ums_cardfile_create_link('SELECT', "/cardfile/join/$source_type/$source_id/work/$match->wid");
             $works[$match->wid] = [
                'Work ID' => $match->wid,
                'Title' => $match->title,
                'Alternate' => $match->alternate,
                'Artists' => "<strong>" . $match->role . ':</strong> ' . $match->artist_name,
                'Notes' => $match->notes,
                'SELECT' => $select_link,
              ];
            }
          }
        }
      } 
      
      elseif ($type == 'artist') {   // ----------------------- ARTIST
        $search_query_parts = [];
        $search_args = [];
        $search_terms_iterator = 0;
        foreach ($search_terms as $search_term) {
          $search_term_placeholder = ':search_term_' . $search_terms_iterator++;
          $search_query_part = "(name LIKE $search_term_placeholder";
          $search_query_part .= " OR name_plain LIKE $search_term_placeholder";
          $search_query_part .= " OR alias LIKE $search_term_placeholder";
          $search_query_part .= " OR notes LIKE $search_term_placeholder)";
          $search_query_parts[] = $search_query_part;
          $search_args[$search_term_placeholder] = '%%' . $search_term . '%%';
        }
        $select_statement = "SELECT * FROM ums_artists WHERE " . implode(' AND ', $search_query_parts) . "ORDER BY name ASC";
        $res = $db->query($select_statement, $search_args)->fetchAll();

        $artists = [];
        foreach ($res as $artist) {
          $select_link = ums_cardfile_create_link('SELECT', '/cardfile/join/' .
                                                            $source_type . '/' .
                                                            $source_id. '/' .
                                                            'artist' . '/' . 
                                                            $artist->aid .'/' . 
                                                            key($query_args) .'/' .
                                                            current($query_args));
          $artists[] = [
            'Artist ID' => $artist->aid,
            'Name' => $artist->name,
            'Alias' => $artist->alias,
            'Notes' => $artist->notes,
            'SELECT' => $select_link,
          ];
        }
      }
    }

    return [
      '#theme' => 'ums-cardfile-searchadd',
      '#heading_text' => $heading_text,
      '#source_type' => $source_type,
      '#source_eid' => $source_eid,
      '#copy_performance_flag' => $copy_performance_flag,
      '#source_event' => $source_event,
      '#search_terms_list' => $search_terms_list,
      '#works' => $works,
      '#artists' => $artists,
      '#source_type' => $source_type,
      '#source_id' => $source_id,
      '#search' => $search,
      '#type' => $type, 
      '#cache' => [ 'max-age' => 0 ]
    ];
  }

  public function cf_autocomplete(Request $request, $type) { 
    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if ($input) {
      $db = \Drupal::database();
      $url_search = \Drupal::request()->query->get('search');
    
      if ($type == 'artist') {
        $artists = $db->query(
          "SELECT * FROM ums_artists " .
          "WHERE name LIKE :input " .
          "OR name_plain LIKE :input " .
          "OR alias LIKE :input " .
          "ORDER BY name ASC LIMIT 25", [':input' => '%%' . $input . '%%']
        )
      ->fetchAll();
        foreach ($artists as $match) {
          $results[] = [
                      'value' => $match->name,
                      'label' => $match->name
                     ];
        }
      } elseif ($type == 'event') {
        $events = $db->query(
          "SELECT * FROM ums_events " .
          "WHERE date LIKE :input " .
          "ORDER BY date ASC LIMIT 25", [':input' => '%%' . $input . '%%']
        )
      ->fetchAll();

        foreach ($events as $match) {
          $match_event = _ums_cardfile_get_event($match['eid']);
          $results[] = [
                      'value' => $match_event->date,
                      'label' => $match_event->date
                     ];
        }
      } elseif ($type == 'work') {
        $works = $db->query(
          "SELECT * FROM ums_works " .
          "WHERE title LIKE :input " .
          "OR alternate LIKE :input " .
          "OR notes LIKE :input " .
          "ORDER BY title ASC LIMIT 25", [':input' => '%%' . $input . '%%']
          )
        ->fetchAll();
        foreach ($works as $match) {
          $results[] = [
                      'value' => $match->title,
                      'label' => $match->title
                     ];
        }
      }
    }
    return new JsonResponse($results);
  }

}