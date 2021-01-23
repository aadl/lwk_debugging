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
    dblog("cf_home: RETURNING: '#theme' => 'ums_cardfile-home'");
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

    dblog("cf_home: RETURNING: '#theme' => 'ums_cardfile_artists'");
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

}
