<?php  if (!defined('PmWiki')) exit();
/**
  Notify about new GitHub releases when editing PmWiki Cookbook pages
  Written by (c) Petko Yotov 2024   www.pmwiki.org/petko
  License: MIT
  
*/

$RecipeInfo['FetchRepoRelease']['Version'] = '2024-04-06';

InsertEditFunction('FetchRepoRelease', '<');

function FetchRepoRelease($pagename, $page, $new) {
  global $MessagesFmt;
  list($g, $n) = explode('.', $pagename);
  $repo = trim(PageTextVar($pagename, 'GitHubRepo'));
  $repo = preg_replace("/\\{($pagename|\\*)?\\\$Name\\}/", $n, $repo);
  if(!$repo || !preg_match('!^\\w+/[-\\w]+$!', $repo)) return;
  $ver = PageTextVar($pagename, 'Version');
  
  @pm_session_start();
  $cached = XL("Reusing cached versions from");
  if(!isset($_SESSION[$repo]) || @$_REQUEST['refreshrepo']) {
    $cached = XL("Downloading versions from");
    $optvars = array(
      'method'=>"GET",
      'header'=>"user-agent: curl/7.68.0\r\n"
      . "accept: */*\r\n"
    );
    $browseropts = array(
      'http' => $optvars,
    );
    $browsercontext = stream_context_create($browseropts);
    
    $url = "https://api.github.com/repos/$repo/releases";
    try {
      $data = @file_get_contents($url, false, $browsercontext);
      if(!$data) $releasedata = [];
      else $releasedata = @json_decode($data, 1);
    }
    catch(Exception $e) {
      $releasedata = [];
    }
    $releases = [];
    if($releasedata) foreach($releasedata as $a) {
      $tag = PHSC($a['tag_name']);
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
    $MessagesFmt[] = "<p><mark>$cached github.com/$repo</mark><br/>\n$msg</p>";
  }
}
