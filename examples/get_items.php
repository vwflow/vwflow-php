<?php
require_once 'VWflow.php';

$email = "xxx"; # email used to log into vwflow.com
$pwd = "xxx";   # password used to log into vwflow.com

try {
  $client = new VWflow($email, $pwd);

  echo "\nGetting Item List ..";
  # get all items
  $response = $client->getItems();
  
  # get for all completed & public video items, created after Fri, 04 Mar 2016 14:01:58 +0000
  // $response = $client->getItems(array("type" => "video", "privacy" => "public", "workflow_status" => "Completed", "created_after" => "1457100118"));

  # get all items tagged with 'news' or 'scoop' (or 'Scoop', or 'SCOOP')
  // $response = $client->getItems("tags" => "news,scoop");

  # search (case insensitive) for all items with 'test' in their name, description, tags or custom metadata
  // $response = $client->getItems(array("search" => "test"));

  echo "\nResponse contains " . $response->count . " items, iterating over them..";
  # iterate over items, echo properties to be used in Wordpress
  foreach($response->results as $item) {
    echo "\n\nFound new Item with ID: " . $item->id;
    echo "\nItem Name: " . $item->name;
    echo "\nItem Description: " . $item->description;
    echo "\nCreated: " . $item->created;
    echo "\nDuration: " . $item->duration;
    echo "\nEmbed Code: " . $item->embed_code;
    foreach($item->tags as $tag) {
      echo "\nTag: " . $tag;
    }
    foreach($item->snapshots as $snapshot) {
      if ($snapshot->id == $item->snapshot_id) {
        echo "\nURL of selected snapshot: " . $snapshot->download;
      }
      else {
        echo "\nURL of available snapshot: " . $snapshot->download;
      }
    }
  }

  while ($response->next) {
    # retrieve next set of results from vwflow (if available)
    echo "\n\nGetting next set of " . $response->count . " items, available at: " . $response->next;
    # send get request for next results page
    $response = $client->getItemsFromUrl($response->next);
    # iterate items, echo properties to be used in Wordpress
    foreach($response->results as $item) {
      echo "\n\nFound new Item with ID: " . $item->id;
      echo "\nItem Name: " . $item->name;
      echo "\nItem Description: " . $item->description;
      echo "\nCreated: " . $item->created;
      echo "\nDuration: " . $item->duration;
      echo "\nEmbed Code: " . $item->embed_code;
      foreach($item->tags as $tag) {
        echo "\nTag :" . $tag;
      }
      foreach($item->snapshots as $snapshot) {
        if ($snapshot->id == $item->snapshot_id) {
          echo "\nURL of selected snapshot: " . $snapshot->download;
        }
        else {
          echo "\nURL of available snapshot: " . $snapshot->download;
        }
      }
    }
  }
  
}
catch(Exception $e) {
  # error message
  echo "\nError msg = " . $e->getMessage();
}
