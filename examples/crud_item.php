<?php
require_once 'VWflow.php';

$email = "xxx"; # email used to log into vwflow.com
$pwd = "xxx";   # password used to log into vwflow.com
$local_video_path = "/path/to/video.mp4"; # path to the video/audio/image to be uploaded

$account = "xxxxx"; # the name of your vwflow account (only used to create download and streaming paths, not necessary if you use the player embed code)
$cdn = "cdn01";   # rambla sub-cdn (only used to create download and streaming paths, not necessary if you use the player embed code)

try {
  $client = new VWflow($email, $pwd);
	
  # create item
  echo "\nCreating Item ..";
  $item = $client->createItem($local_video_path, "Video Title", "Video Description", array("Tag 1", "Tag 2"));
  echo "\nItem ID: " . $item->id;
  echo "\nItem URL: " . $item->url;
  
  # update item
  echo "\n\nUpdating Item ..";
  $item->name = "My Title";
  $item->description = "My Description";
  $item->tags = array("My First Tag", "My Second Tag");
  $item = $client->updateItem($item);
  echo "\nItem Name: " . $item->name;
  echo "\nItem Description: " . $item->description;
  foreach($item->tags as $tag) {
    echo "\nTag: " . $tag;
  }

  # poll item until workflow is complete (= alternative to receiving HTTP POST on completion)
  echo "\n\nGetting Item (waiting until workflow is completed) ..";
  $processed = False;
  while(! $processed) {
    sleep(3);
    $item = $client->getItem($item->id);
    if ($item->workflow->status == "Completed") {
      echo "\nItem Embed Code: " . $item->embed_code; # use the vwflow player's embed code for integration in your site or CMS
      echo "\nItem type: " . $item->type;
      echo "\nDuration: " . $item->duration . " seconds";
      # Get download & streaming URLs (not recommended, use the embed code instead)
      echo "\nHLS Streaming URL: http://$account.fstreams.$cdn.rambla.be/$account/_definst_/smil:" . $item->path . "/playlist.m3u8";
      echo "\nDownload URL: http://$account.$cdn.rambla.be" . $item->direct_path;
      # Get available snapshots, and select a new snapshot (poster, thumbnail image) for the video
      $new_snapshot_id = null;
      foreach($item->snapshots as $snapshot) {
        if ($snapshot->id == $item->snapshot_id) {
          echo "\nURL of selected snapshot: " . $snapshot->download;
        }
        else {
          echo "\nURL of available snapshot: " . $snapshot->download;
          if (! $new_snapshot_id) {
            $new_snapshot_id = $snapshot->id;
          }
        }
      }
      # set the new snapshot
      if ($new_snapshot_id) {
        $client->setItemSnapshot($item->id, $new_snapshot_id);
        echo "\nNew snapshot set successfully";
      }
      $processed = True;
    }
    else if ($item->workflow->status == "Failed") {
      echo "\nWorkflow Failed, last workflow action was " . $item->workflow->curr_action;
      $processed = True;
    }
  }

  # delete item
  echo "\n\nDeleting Item ..";
  $client->deleteItem($item->id);
  echo "\nItem deleted!";
  
}
catch(Exception $e) {
  # error message
  echo "\nError msg = " . $e->getMessage();
}