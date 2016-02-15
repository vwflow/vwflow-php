<?php
# Sample to demonstrate how to generate a form that uses XMLHttpRequest to upload files directly to VWflow.
# This is done using the VWflow 'items-hmac-redirect' resource, which uses HMAC authentication.
#  Data is transfered using X-VWFLOW-INFO and X-VWFLOW-HMAC headers.
#
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

# required variables
$account_id = "xxxxxxx";                                 # id of your VWflow account
$workflow_profile_id = "xxxxxxx";                        # workflow profile id to be used
$secret = "xxxxxxxxxxxx";                                # the auth_secret from the workflow profile, should not be made public !!
$redirect = "http://example.com/";                       # URL to redirect to after upload is completed

$msg_data = uniqid(rand(), true);               # generate unique msg_data value (for HMAC)
date_default_timezone_set('Europe/Brussels');
$msg_timestamp = time() + (3 * 60 * 60);        # requests using this page will be valid during three hours
$hmac = md5($secret.$msg_timestamp.$msg_data);  # generate the HMAC

# optional variables
$client_data = "";

$html = <<<EOT
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta charset="utf-8" />
  	<title>VWflow API - upload with form using the 'items-hmac-redirect' endpoint</title>
  </head>
  <body>
  <h1>Demo to create VWflow item with user generated content</h1>
  <p>This sample demonstrates the use of XMLHttpRequest to upload files directly to VWflow. This is done using the 'items-hmac-redirect' API endpoint and HMAC authentication. Data is transferred in the X-VWFLOW-INFO and X-VWFLOW-HMAC headers.</p>
  
  <p id="support-notice">Your browser does not support Ajax uploads : you can only use this sample through a regular upload (with hidden form fields).</p>

  <form action="https://workflow.workflow02.rambla.be/api/v1/items-hmac-redirect/$account_id/$workflow_profile_id/" method="post" enctype="multipart/form-data" id="form-id">
  <p><label>File to encode and publish : </label> <input id="file-id" type="file" name="our-file" /></p>
  <p><label>Client Data (json string with double quotes escaped) : </label> <input name="client_data" type="text" id="client_data" /></p>
  <p><input type="submit" value="Submit" /></p>
  
   <!-- fallback to hidden form fields (and default client_data) for browsers that don't support ajax uploads -->
   <input type="hidden" name="vwflow_info" value="{&quot;redirect&quot;:&quot;$redirect&quot;,&quot;msg_data&quot;:&quot;$msg_data&quot;,&quot;msg_timestamp&quot;:&quot;$msg_timestamp&quot;,&quot;client_data&quot;:&quot;$client_data&quot;}" />
  <input type="hidden" name="vwflow_hmac" value="$hmac"></form>

  <script>
  // Function that will allow us to know if Ajax uploads are supported
  function supportAjaxUploadWithProgress() {
    return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();

    // Is the File API supported?
    function supportFileAPI() {
      var fi = document.createElement('INPUT');
      fi.type = 'file';
      return 'files' in fi;
    };

    // Are progress events supported?
    function supportAjaxUploadProgressEvents() {
      var xhr = new XMLHttpRequest();
      return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
    };

    // Is FormData supported?
    function supportFormData() {
      return !! window.FormData;
    }
  }

  // Actually confirm support
  if (supportAjaxUploadWithProgress()) {
    // Ajax uploads are supported!
    // Change the support message and enable the upload button
    var notice = document.getElementById('support-notice');
    // var uploadBtn = document.getElementById('upload-button-id');
    notice.innerHTML = "Your browser supports HTML uploads. Your form will be sent using an XMLHttpRequest.";
    // uploadBtn.removeAttribute('disabled');

    // Init the Ajax form submission
    initFullFormAjaxUpload();
  }

  function initFullFormAjaxUpload() {
    var form = document.getElementById('form-id');
    form.onsubmit = function() {
      // FormData receives the whole form
      var formData = new FormData(form);

      // We send the data where the form wanted
      var action = form.getAttribute('action');

      // Code common to both variants
      sendXHRequest(formData, action);

      // Avoid normal form submission
      return false;
    }
  }

  // Once the FormData instance is ready and we know
  // where to send the data, the code is the same
  // for both variants of this technique
  function sendXHRequest(formData, uri) {
    // Get an XMLHttpRequest instance
    var xhr = new XMLHttpRequest();

    // Set up events
    xhr.upload.addEventListener('loadstart', onloadstartHandler, false);
    xhr.upload.addEventListener('progress', onprogressHandler, false);
    xhr.upload.addEventListener('load', onloadHandler, false);

    // Set up request
    xhr.open('POST', uri, true);

    // Set up request
    var passthru = document.getElementById('client_data').value;
    if (! passthru) {
      passthru = "$client_data";
    }
    var vwflow_info = '{"redirect":"$redirect", "msg_data":"$msg_data", "msg_timestamp":"$msg_timestamp", "client_data":"' + passthru + '"}';
    xhr.setRequestHeader("X-VWFLOW-INFO", vwflow_info);
    xhr.setRequestHeader("X-VWFLOW-HMAC", "$hmac");
    xhr.addEventListener('readystatechange', onreadystatechangeHandler, false);

    // Fire!
    xhr.send(formData);
  }

  // Handle the start of the transmission
  function onloadstartHandler(evt) {
    var div = document.getElementById('upload-status');
    div.innerHTML = 'Upload started!';
  }

  // Handle the end of the transmission
  function onloadHandler(evt) {
    var div = document.getElementById('upload-status');
    div.innerHTML = 'Upload successful!';
  }

  // Handle the progress
  function onprogressHandler(evt) {
    var div = document.getElementById('progress');
    var percent = evt.loaded/evt.total*100;
    div.innerHTML = 'Progress: ' + percent + '%';
  }

  // Handle the response from the server
  function onreadystatechangeHandler(evt) {
    var status = null;

    try {
      console.log(evt.target.status)
      status = evt.target.status;
    }
    catch(e) {
      return;
    }

    if (status == '200' && evt.target.responseText) {
      var result = document.getElementById('result');
      result.innerHTML = '<p>The server returned following response:</p><pre>' + evt.target.responseText + '</pre>';
    }
  }
    </script>

    <!-- Placeholders for messages set by event handlers -->
    <p id="upload-status"></p>
    <p id="progress"></p>
    <pre id="result"></pre>

  </form>
  </body>
  </html>


EOT;

$file = './form_for_srcencode_ajax.html';
file_put_contents($file, $html);


?>