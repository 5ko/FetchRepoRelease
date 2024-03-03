<?php  if (!defined('PmWiki')) exit();
/**
  Notify about new GitHub releases when editing PmWiki Cookbook pages
  Written by (c) Petko Yotov 2024   www.pmwiki.org/petko
  License: MIT
  
*/

$RecipeInfo['FetchRepoRelease']['Version'] = '2024-03-03';

InsertEditFunction('FetchRepoRelease', '<');

function FetchRepoRelease($pagename, $page, $new) {
  global $MessagesFmt;
  $repo = trim(PageTextVar($pagename, 'GitHubRepo'));
  if(!$repo || !preg_match('!^\\w+/[-\\w]+$!', $repo)) return;
  $ver = PageTextVar($pagename, 'Version');
  
  $cached = "Reusing cached versions";
  if(!isset($_SESSION[$repo]) || @$_REQUEST['refreshrepo']) {
    $cached = "Downloading versions";
    $optvars = array(
      'method'=>"GET",
      'header'=>"user-agent: curl/7.68.0\r\n"
      . "accept: */*\r\n"
    );
    $browseropts = array(
      'https' => $optvars,
      'http'  => $optvars,
    );
    $browsercontext = stream_context_create($browseropts);
    
    $url = "https://api.github.com/repos/$repo/releases";
    $releasedata = json_decode(file_get_contents($url, false, $browsercontext), 1);
    $releases = [];
    foreach($releasedata as $a) {
      $tag = $a['tag_name'];
      $notes = trim(preg_replace('/\\s+/', ' ', PHSC($a['body'])));
      $releases[$tag] = $notes;
    }
    
    $_SESSION[$repo] = $releases;
  }
  else $releases = $_SESSION[$repo];
  
  $msg = '';
  foreach($releases as $tag=>$notes) {
    if($tag <= $ver) break;
    $msg .= "<mark>* <b>$tag</b>: $notes</mark><br/>\n";
  }
  if($msg) {
    $MessagesFmt[] = "<mark>$cached from github.com/$repo</mark><br/>\n$msg";
  }
  
}
