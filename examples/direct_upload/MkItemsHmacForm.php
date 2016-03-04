<?php
# Sample to demonstrate how to generate a basic form that allows end users to upload a file directly to VWflow
# This is done using the VWflow 'items-hmac-redirect' resource, which uses HMAC authentication.
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
$account_id = "xxxxxxxx";                           # id of your VWflow account
$workflow_profile_id = "xxxxxxxx";                  # workflow profile id to be used
$secret = "xxxxxxxxxxxxx";                          # the auth_secret from the workflow profile, should not be made public !!
$redirect = "http://example.com/";                  # URL to redirect to after upload is completed

$msg_data = uniqid(rand(), true);               # generate unique msg_data value (for HMAC)
date_default_timezone_set('Europe/Brussels');
$msg_timestamp = time() + (3 * 60 * 60);        # requests using this page will be valid during three hours
$hmac = md5($secret.$msg_timestamp.$msg_data);  # generate the HMAC

# optionally send client_data as string
$client_data = "some client specific data"; # client-specific data that can be retrieved later (via GET job or CDN report notification)
# or send client_data as json object
$arr = array("a" => 1, "b" => 2);
$client_data = json_encode($arr);
$client_data = addcslashes($client_data, '"');
$client_data = htmlspecialchars($client_data);

$html = <<<EOT
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
	<title>VWflow API - upload with form using the 'items-hmac-redirect' endpoint</title>
  </head>
  <body>
  <h1>Demo to create VWflow item with user generated content</h1>
  <p>This sample demonstrates how to create a form for uploading files directly to VWflow. This is done using the 'items-hmac-redirect' API endpoint and HMAC authentication.</p>

    <form id="MyForm" action="https://vwflow.com/api/v1/items-hmac-redirect/$account_id/$workflow_profile_id/" enctype="multipart/form-data" method="POST">
    <fieldset>
    <input type="file" name="file1" /><br />
    <input type="file" name="file2" /><br />
    <input type="submit" value="Upload">
    <input type="hidden" name="vwflow_info" value="{&quot;redirect&quot;:&quot;$redirect&quot;,&quot;msg_data&quot;:&quot;$msg_data&quot;,&quot;msg_timestamp&quot;:&quot;$msg_timestamp&quot;,&quot;client_data&quot;:&quot;$client_data&quot;}" />
    <input type="hidden" name="vwflow_hmac" value="$hmac">
    </fieldset>
    </form>
  </body>
  </html>
EOT;

$file = './form_for_items_hmac_redirect.html';
file_put_contents($file, $html);


?>