<?php

/**
 * @description Creates a class to manage a pdf file scan requests
 * to AWS lambda
 * @param {String} apiBaseURL
 * @param {String} apiKey
 */

class PDF_Scan_Handler {

  function __construct($apiBaseURL, $apikey) {
    $this->apiBaseURL = $apiBaseURL;
    $this->apikey = $apikey;
  }

  private function handleError($error) {
    throw new Exception($error, 1);
  }

  /**
   * @description This function will GET the presigned URL from AWS that will allow us to post the file
   * @params String $url
   * @returns StdClass
   */
  public function getPresignedURL($url) {

    $curl_url = $this->apiBaseURL . $url;
    $ch = curl_init($curl_url);

    $headers = [
      'Content-Type: application/json',
      'Connection: Keep-Alive',
      'x-api-key: ' . $this->apikey
    ];

    $opts = [
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_RETURNTRANSFER => true, //return the web page
      CURLOPT_FOLLOWLOCATION => true, // follow redirects
      CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
      CURLOPT_CONNECTTIMEOUT => 120, // time out on conect
      CURLOPT_TIMEOUT => 120, // time out on response
    ];

    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);

    // Handle errors
    if (curl_error($ch)) {
      $error = curl_error($ch);
      $this->handleError($error);
    }

    curl_close($ch);

    $json = json_decode($res);

    $returnVals = new stdClass();
    $returnVals->uploadURL = $json->uploadURL;
    $returnVals->key = $json->key;

    return $returnVals;
  }

  /**
   * @description This function will put the file into
   * an AWS S3 bucket
   * @param String presignedURL
   * @param String key
   * @param String file_path
   * @returns Boolean
   */
  public function putFile($url, $key, $file) {
    $ch = curl_init($url);
    $file_size = filesize($file);

    $headers = [
      'filename: ' . $key,
      'Content-Length: ' . $file_size
    ];

    $opts = [
      CURLOPT_PUT => true,
      CURLOPT_INFILE => $file,
      CURLOPT_INFILESIZE => $file_size,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 60, // time out on conect
      CURLOPT_TIMEOUT => 60, // time out on response
    ];

    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);

    // Handle errors
    if (curl_error($ch)) {
      $error = curl_error($ch);
      $this->handleError($error);
    }

    curl_close($ch);

    return true;
  }

  /**
  * @description This function will trigger a lambda function in
  * AWS
  * @params String $url
  * @params String $key
  * @returns StdClass
  */
  public function scanFile($url, $key) {
    $curl_url = $this->apiBaseURL . $url;
    $headers = [
      'Content-Type: application/json',
      'x-api-key: ' . $this->apikey
    ];
    $body = json_encode([ 'key' => $key ]);

    $opts = [
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 60, // time out on conect
      CURLOPT_TIMEOUT => 60, // time out on response
    ];

    $ch = curl_init($curl_url);

    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);

    // Handle errors
    if (curl_error($ch)) {
      $error = curl_error($ch);
      $this->handleError($error);
    }

    curl_close($ch);

    $json = json_decode($res);

    return $json;
  }

}