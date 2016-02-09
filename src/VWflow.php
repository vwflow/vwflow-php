<?php
# PHP Client Library for VWflow
# Copyright (C) 2016 rambla.eu
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

require_once 'Requests.php';
Requests::register_autoloader(); //make sure Requests can load internal classes

/**
 * Generic VWflow API client.
 */
class VWflow
{
  var $uri;
  var $username;
  var $password;
  var $ssl;
  var $user_agent;
  var $dflt_get_args;
  var $request_options;
	
  /**
   * Constructor.
   *
   * @param string $username Username for Basic Authentication to VWflow
   * @param string $pwd Password for Basic Authentication to VWflow
   * @param bool $ssl True for SSL requests
   * @param string $user_agent User agent name
   * @param array $dflt_get_args Array of querystring args (key, value) to be used in every GET request (eg array("workflow_status" => "Completed", "privacy" => "public"))
   * @param string $custom_host To use a custom VWflow host name 
   * @param string or bool $ssl_verify May contain either the '/path/to/cacert.pem' or False 
   */
	function __construct($username, $pwd, $ssl = True, $user_agent = null, $dflt_get_args = null, $custom_host = null, $ssl_verify = null) 
	{
    $this->username = $username;
    $this->password = $pwd;
    $this->ssl = $ssl;
    $this->uri = "http://vwflow.com/api/v1/";
    if ($this->ssl) {
      $this->uri = "https://vwflow.com/api/v1/";
    }
    if ($custom_host) {
      $this->uri = "http://$custom_host/api/v1/";
      if ($this->ssl) {
        $this->uri = "https://$custom_host/api/v1/";
      }
    }
    $this->default_get_args = array();
    if ($dflt_get_args) {
      $this->default_get_args = $dflt_get_args;
    }
    $this->user_agent = "VWflow 1.0";
    if ($user_agent) {
      $this->user_agent = $user_agent;
    }
    # set basic auth credentials in request options
    $this->request_options = array('auth' => new Requests_Auth_Basic(array($this->username, $this->password)) );
    if (!is_null($ssl_verify)) {
      $this->request_options['verify'] = $ssl_verify;
    }
    # set timeout (in seconds) for POSTing large files - avoids curl timing out if no response is returned by the server after 10 seconds (even if upload is still busy)
    # Note: there may be additional restrictions in php.ini (max_execution_time, memory_limit, post_max_size, upload_max_filesize)
    $this->upload_timeout = 500;
	}
  
  /**
   * Check if error response was received from VWflow.
   *
   * @param Requests_Response $response HTTP Response class
   * @throws Exception If an error response was returned by VWflow
   */
  function _checkErrorResponse($response) {
    if (! $response->success) {
      $error_msg = "";
      if ($response->body) {
        $errbody = json_decode($response->body);
        if ($errbody) {
          if (isset($errbody->detail)) {
            $error_msg = $errbody->detail;
          }
        }
        if (! $error_msg) {
          $error_msg = $response->body;
        }
      }
      if (! $error_msg) {
        $error_msg = "Internal Error";
      }
      throw new Exception($error_msg);
    }
  }
  
  /**
   * Do an items query by passing along the parameters.
   *
   * Returns a json decoded object containing the data returned by vwflow.
   * For a list of query-string arguments, see https://vwflow.com/api/v1/items/
   *
   * @param array $args Query-string arguments (key, value) to be sent as part of the get request. 
   *        Values must already be formatted as query-string arguments (eg. array("search" => "bunny", "tags" => "one,two", "page" => 1))
   * @return stdClass Object containing the JSON decoded response body 
   * @throws Exception If an error response was returned by VWflow
   */
	function getItems($args = array())
	{
    $qstr = "?";
    foreach ($this->default_get_args as $key => $value) {
      $qstr = $qstr . "$key=$value&";
    }
  
    $page_set = False;
    foreach($args as $key => $value) {
      if (! is_null($value) ) {
        $qstr = $qstr . "$key=$value&";
        if (strtolower($key) == "page") {
          $page_set = True;
        }
      }   
    }

    if ($page_set) {
      $qstr = rtrim($qstr, "&");
    }
    else {
      $qstr = $qstr . 'page=1';
    }
      
    $url = $this->uri . "items/" . $qstr;
    $response = Requests::get($url, array(), $this->request_options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}
  
  /**
   * Do an items query by passing along the full url, already containing the query-string arguments.
   *
   * Returns a json decoded object containing the data returned by vwflow.
   * For more details, see https://vwflow.com/api/v1/items/
   *
   * @param string $url Full Items Query URL
   * @return stdClass Object containing the JSON decoded response body
   * @throws Exception If an error response was returned by VWflow
   */
	function getItemsFromUrl($url)
	{
    $response = Requests::get($url, array(), $this->request_options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}

  /**
   * Create a new item by uploading a file (POST Items request).
   *
   * @param string $path Absolute path to the file being uploaded.
   * @param string $name Name to be given to the created item.
   * @param string $description Description to be given to the created item.
   * @param array $tags Tags to be attached to the created item.
   * @param array $meta Custom metadata to be attachted to the created item.
   * @param string $wprofile_id ID of the workflow profile to be used for processing the item.
   * @param JSON Encoded Data $client_data JSON encoded client-specific data.
   * @return stdClass Object containing the JSON decoded response body
   * @throws Exception If an error response was returned by VWflow
   */
	function createItem($path, $name = null, $description = null, $tags = null, $schema = null, $wprofile_id = null, $client_data = null, $input_data = null, $producer = null, $language = null, $task_to_mails = null)
	{
    $a_item = array("src" => "@$path", "name" => $name, "description" => $description, "wprofile_id" => $wprofile_id);
    $data = array();
    if ($tags) {
      $data["tags"] = $tags;
    }
    if ($schema) {
      $data["schema"] = $schema;
    }
    if ($producer) {
      $data["producer"] = $producer;
    }
    if ($language) {
      $data["language"] = $language;
    }
    if ($task_to_mails) {
      $data["tasks"] = array("send_mail_to" => $task_to_mails);
    }
    if ($data) {
      $data = json_encode($data);
      $a_item["data"] = $data;
    }
    if ($client_data) {
      $a_item["client_data"] = $client_data;
    }
    if ($input_data) {
      $a_item["input_data"] = $input_data;
    }
    $url = $this->uri . "items/";
    $headers = array('Content-Type' => 'multipart/form-data');
    $hooks = new Requests_Hooks();
    $hooks->register('curl.before_send', function($fp, $data = array()) use ($a_item){
        curl_setopt($fp, CURLOPT_POSTFIELDS, $a_item);
        curl_setopt($fp, CURLOPT_TIMEOUT, $this->upload_timeout);
    });
    $options = $this->request_options;
    $options['hooks'] = $hooks;
    $response = Requests::post($url, $headers, array(), $options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}

  /**
   * Get the metadata of an existing item (GET items/{id} request).
   *
   * @param string $item_id ID of the item.
   * @return stdClass Object containing the item data (JSON decoded).
   * @throws Exception If an error response was returned by VWflow
   */
	function getItem($item_id)
	{
    $url = $this->uri . "items/" . $item_id . "/";
    $headers = array('Content-Type' => 'application/json');
    $response = Requests::get($url, array(), $this->request_options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}
  
  /**
   * Update the metadata of an existing item (PUT items/{id} request).
   *
   * @param stdClass $item_obj Object containing the item data (to be JSON encoded).
   * @return stdClass Object containing the JSON decoded response body (= same as $item_obj)
   * @throws Exception If an error response was returned by VWflow
   */
	function updateItem($item_obj)
	{
    $url = $this->uri . "items/" . $item_obj->id . "/";
    $headers = array('Content-Type' => 'application/json');
    $response = Requests::put($url, $headers, json_encode($item_obj), $this->request_options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}

  /**
   * Send a PUT items request to vwflow to update the snapshot that is being used for a given item.
   *
   * @param string $item_id ID of the item.
   * @param int $snapshot_id ID of the snapshot to be set.
   * @throws Exception If an error response was returned by VWflow
   */
	function setItemSnapshot($item_id, $snapshot_id)
	{
    $url = $this->uri . "items/" . $item_id . "/";
    $headers = array('Content-Type' => 'application/json');
    $response = Requests::put($url, $headers, json_encode(array("snapshot_id" => $snapshot_id)), $this->request_options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}

  /**
   * Delete an existing item (DELETE items/{id} request).
   *
   * @param stdClass $item_id ID of the item to be deleted.
   * @throws Exception If an error response was returned by VWflow
   */
	function deleteItem($item_id)
	{
    $url = $this->uri . "items/" . $item_id . "/";
    $headers = array('Content-Type' => 'application/json');
    $response = Requests::delete($url, $headers, $this->request_options);
    $this->_checkErrorResponse($response);
	}

	/**
   * POST a file to VWflow's items-hmac endpoint.
   *
   * If the request is valid, a new item will be created.
   *
   * @param string $local_path Local path to the file that needs to be uploaded.
   * @param string $account_id ID of your VWflow account.
   * @param string $wprofile_id ID of your VWflow workflow profile.
   * @param string $auth_secret Shared secret attached to your workflow profile.
   * @param int $hmac_valid_seconds Number of seconds during which the HMAC will be considered as valid by VWflow (default = 30).
   * @param string $client_data Client specific data in JSON format (should be valid json, use json_encode() to generate it !!),
   *          which can be retrieved later as part of a notification or as the result of polling the job resource (optional).
   * @return JSON object containing the URL(s) required to retrieve the item's data through the VWflow items API, or containing an error
   */
 	function createItemWithHmacAuth($local_path, $account_id, $wprofile_id, $auth_secret, $hmac_valid_seconds = 30, $client_data = "")
	{
    $a_item = array("src" => "@$local_path");
    $url = $this->uri . "items-hmac/$account_id/$wprofile_id/";
	  $msg_data = uniqid(rand(), true); # generate unique id
    date_default_timezone_set('Europe/Brussels');
    $msg_timestamp = time() + $hmac_valid_seconds; # requests using this page will be valid during three hours
    $vwflow_hmac = md5($auth_secret.$msg_timestamp.$msg_data);

    $vwflow_info = <<<EOT
{"msg_data":"$msg_data","msg_timestamp":"$msg_timestamp","client_data":"$client_data"}
EOT;
    $headers = array('Content-Type' => 'multipart/form-data', 'x-vwflow-info' => $vwflow_info, "x-vwflow-hmac" => $vwflow_hmac);
    $hooks = new Requests_Hooks();
    $hooks->register('curl.before_send', function($fp, $data = array()) use ($a_item){
        curl_setopt($fp, CURLOPT_POSTFIELDS, $a_item);
        curl_setopt($fp, CURLOPT_TIMEOUT, $this->upload_timeout);
    });
    $options = $this->request_options;
    $options['hooks'] = $hooks;
    $response = Requests::post($url, $headers, array(), $options);
    $this->_checkErrorResponse($response);
    return json_decode($response->body);
	}
  

}

?>