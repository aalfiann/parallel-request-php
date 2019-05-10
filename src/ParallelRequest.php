<?php 
namespace aalfiann;
    /**
     * A PHP class to create multiple request in parallel
     *
     * @package    ParallelRequest Class
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/parallel-request-php/blob/master/LICENSE.md  MIT License
     */
    class ParallelRequest {

        var $request,$delayTime=10000,$encoded=false,$httpStatusOnly=false,$httpInfo=false,$options=array(),$response=array();
        
        /**
         * Set request
         * @param request = input the request url here (string or array)
         * @return this for chaining purpose
         */
        public function setRequest($request){
            $this->request = $request;
            return $this;
        }

        /**
         * Add request
         * @param url = input the request url here (string only)
         * @param params = is the array parameter data to be send for the request (array). This is optional and default is empty.
         * @param formdata = if set to false then will convert params array to url parameter. Default is true means as form-data.
         * @return this
         */
        public function addRequest($url,$params=array(),$formdata=true){
            if(!empty($params)){
                if ($formdata){
                    $this->request[] = [
                        'url' => $url,
                        'post' => $params
                    ];
                } else {
                    $this->request[] = $url.'?'.(!empty($params)?http_build_query($params,'','&'):'');
                }
            } else {
                $this->request[] = $url;
            }
            return $this;
        }

        /**
         * Add request for raw data
         * @param url = input the request url here (string only)
         * @param data = is the raw data to be send for the request. This is required and not encoded by default.
         * @return this
         */
        public function addRequestRaw($url,$data){
            $this->request[] = [
                'url' => $url,
                'post' => $data
            ];
            return $this;
        }

        /**
         * Set cURL options
         * @param options = create your multiple cURL options into array
         * @return this for chaining purpose 
         */
        public function setOptions($options=array()){
            $this->options = $options;
            return $this;
        }

        /**
         * Set Http Status Only
         * @param httpStatusOnly = if set to true then the response only display the http status code only
         * @return this for chaining purpose
         */
        public function setHttpStatusOnly($httpStatusOnly=true){
            $this->httpStatusOnly = $httpStatusOnly;
            return $this;
        }

        /**
         * Set Http Info
         * @param httpInfo = if set to true then the response will display the http info status. Set to "detail" for more info.
         * @return this for chaining purpose
         */
        public function setHttpInfo($httpInfo=true){
            $this->httpInfo = $httpInfo;
            return $this;
        }

        /**
         * Set encoded data post
         * @param encoded if set true then data post will be url encoded.
         * @return this for chaining purpose
         */
        public function setEncoded($encoded=true){
            $this->encoded = $encoded;
            return $this;
        }

        /**
         * Set delay time for cpu to take a rest
         * @param time = input the delay execution time. Default is 10000 (10ms) in microseconds.
         * @return this for chaining purpose
         */
        public function setDelayTime($time=10000){
            $this->delayTime = $time;
            return $this;
        }
        
        /**
         * Send a parallel request with using curl_multi_exec
         * @return this for chaining purpose
         */
        public function send() {

            if(!extension_loaded('curl')) throw new Exception('CURL library not loaded!');

            // cleanup any response
            $this->response = array();
 
            // array of curl handles
            $curly = array();
            
            // data to be returned
            $result = array();
            
            // multi handle
            $mh = curl_multi_init();
            
            // make sure request is array
            if (is_string($this->request)) $this->request = array($this->request);
           
            // loop through data request and create curl handles then add them to the multi-handle
            foreach ($this->request as $id => $d) {
                $curly[$id] = curl_init();
                $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
                curl_setopt($curly[$id], CURLOPT_URL, $url);
                if ($this->httpStatusOnly) curl_setopt($curly[$id], CURLOPT_NOBODY, 1);
           
                // contains data to post?
                if (is_array($d)) {
                    if (!empty($d['post'])) {
                        curl_setopt($curly[$id], CURLOPT_POST,       1);
                        if ($this->encoded && is_array($d['post'])) {
                            curl_setopt($curly[$id], CURLOPT_POSTFIELDS, http_build_query($d['post']));
                        } else {
                            curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
                        }
                    }
                }
           
                // set options?
                if (!empty($this->options)) {
                    curl_setopt_array($curly[$id], $this->options);
                } else {
                    curl_setopt($curly[$id], CURLOPT_HEADER,         0);
                    curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
                }

                // get request headers for httpInfo detail only
                if ($this->httpInfo && $this->httpInfo == 'detail') {
                    curl_setopt($curly[$id], CURLINFO_HEADER_OUT, 1);
                    $resheaders = [];
                    curl_setopt($curly[$id], CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$resheaders) {
                        $len = strlen($header);
                        $header = explode(':', $header, 2);
                        if (count($header) >= 2) {
                            $resheaders[trim($header[0])] = trim($header[1]);
                        }
                        return $len;
                    });
                }

                // activate curl response message
                if($this->httpInfo === 'detail'){
                    if (empty($this->options[CURLOPT_FAILONERROR]) || (!empty($this->options[CURLOPT_FAILONERROR]) && $this->options[CURLOPT_FAILONERROR] == false)){
                        curl_setopt($curly[$id], CURLOPT_FAILONERROR, 1);
                    }
                }
           
                curl_multi_add_handle($mh, $curly[$id]);
            }
           
            // execute the handles
            $running = null;
            do {
                $mrc =curl_multi_exec($mh, $running);
            } while($mrc == CURLM_CALL_MULTI_PERFORM);

            // perform multi select
            while ($running && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) == -1) {
                    // delay time for cpu take a rest in every 10ms
                    usleep($this->delayTime);
                }
            
                do {
                    $mrc = curl_multi_exec($mh, $running);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
           
            // get content and remove handles
            foreach($curly as $id => $c) {
                if ($this->httpStatusOnly){
                    $result[$id] = curl_getinfo($c, CURLINFO_HTTP_CODE);
                } else {
                    if ($this->httpInfo){
                        $curl_errno = curl_errno($c);
                        if($this->httpInfo === 'detail'){
                            $reqheaders = [];
                            $outHeaders = explode("\r\n", curl_getinfo($c, CURLINFO_HEADER_OUT));
                            $outHeaders = array_filter($outHeaders, function($value) use (&$reqheaders) { 
                                $len = strlen($value);
                                $value = explode(':', $value, 2);
                                if (count($value) >= 2) {
                                    $reqheaders[trim($value[0])] = trim($value[1]);
                                } else {
                                    if(!empty(trim($value[0]))) $reqheaders[] = trim($value[0]);
                                }
                                return $len;
                            });
                            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
                            $curl_error = curl_error($c);
                            $result[$id] = [
                                'code' => $http_code,
                                'info' => [
                                    'headers' => [
                                        'request' => $reqheaders,
                                        'response' => $resheaders,
                                    ],
                                    'url' => curl_getinfo($c, CURLINFO_EFFECTIVE_URL),
                                    'content_type' => curl_getinfo($c, CURLINFO_CONTENT_TYPE),
                                    'content_length' => curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD),
                                    'total_time' => curl_getinfo($c, CURLINFO_TOTAL_TIME),
                                    'debug' => 'CURLcode ['.$curl_errno.']: '.$this->getCurlCode($curl_errno),
                                    'message' => (($curl_error)?$curl_error:(($http_code == 0)?'The requested URL returned error: Unknown':'Request URL finished'))
                                ],
                                'response' => curl_multi_getcontent($c)
                            ];
                        } else {
                            $result[$id] = [
                                'code' => curl_getinfo($c, CURLINFO_HTTP_CODE),
                                'debug' => $this->getCurlCode($curl_errno),
                                'response' => curl_multi_getcontent($c)
                            ];
                        }
                    } else {
                        $result[$id] = curl_multi_getcontent($c);
                    }
                }
                curl_multi_remove_handle($mh, $c);
            }
           
            // close when all is done
            curl_multi_close($mh);
            if (count($result) <= 1) {
                $this->response = $result[0];
            } else {
                $this->response = $result;
            }
            return $this;
        }
        
        /**
         * Get response from request
         * @return mixed array or string
         */
        public function getResponse(){
            if(!empty($this->response)) return $this->response;
            return null;
        }

        /**
         * Get response from request with json format
         * @return json
         */
        public function getResponseJson(){
            if(!empty($this->response)) return json_encode($this->response);
            return null;
        }

        /**
         * Get Curl code information
         * @param num is the number from curl_errno
         * @return string
         */
        public function getCurlCode($num){
            $curl_codes = [
                0 => 'CURLE_OK',
                1 => 'CURLE_UNSUPPORTED_PROTOCOL',
                2 => 'CURLE_FAILED_INIT',
                3 => 'CURLE_URL_MALFORMAT',
                4 => 'CURLE_URL_MALFORMAT_USER',
                5 => 'CURLE_COULDNT_RESOLVE_PROXY',
                6 => 'CURLE_COULDNT_RESOLVE_HOST',
                7 => 'CURLE_COULDNT_CONNECT',
                8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
                9 => 'CURLE_REMOTE_ACCESS_DENIED',
                11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
                13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
                14 => 'CURLE_FTP_WEIRD_227_FORMAT',
                15 => 'CURLE_FTP_CANT_GET_HOST',
                17 => 'CURLE_FTP_COULDNT_SET_TYPE',
                18 => 'CURLE_PARTIAL_FILE',
                19 => 'CURLE_FTP_COULDNT_RETR_FILE',
                21 => 'CURLE_QUOTE_ERROR',
                22 => 'CURLE_HTTP_RETURNED_ERROR',
                23 => 'CURLE_WRITE_ERROR',
                25 => 'CURLE_UPLOAD_FAILED',
                26 => 'CURLE_READ_ERROR',
                27 => 'CURLE_OUT_OF_MEMORY',
                28 => 'CURLE_OPERATION_TIMEDOUT',
                30 => 'CURLE_FTP_PORT_FAILED',
                31 => 'CURLE_FTP_COULDNT_USE_REST',
                33 => 'CURLE_RANGE_ERROR',
                34 => 'CURLE_HTTP_POST_ERROR',
                35 => 'CURLE_SSL_CONNECT_ERROR',
                36 => 'CURLE_BAD_DOWNLOAD_RESUME',
                37 => 'CURLE_FILE_COULDNT_READ_FILE',
                38 => 'CURLE_LDAP_CANNOT_BIND',
                39 => 'CURLE_LDAP_SEARCH_FAILED',
                41 => 'CURLE_FUNCTION_NOT_FOUND',
                42 => 'CURLE_ABORTED_BY_CALLBACK',
                43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
                45 => 'CURLE_INTERFACE_FAILED',
                47 => 'CURLE_TOO_MANY_REDIRECTS',
                48 => 'CURLE_UNKNOWN_TELNET_OPTION',
                49 => 'CURLE_TELNET_OPTION_SYNTAX',
                51 => 'CURLE_PEER_FAILED_VERIFICATION',
                52 => 'CURLE_GOT_NOTHING',
                53 => 'CURLE_SSL_ENGINE_NOTFOUND',
                54 => 'CURLE_SSL_ENGINE_SETFAILED',
                55 => 'CURLE_SEND_ERROR',
                56 => 'CURLE_RECV_ERROR',
                58 => 'CURLE_SSL_CERTPROBLEM',
                59 => 'CURLE_SSL_CIPHER',
                60 => 'CURLE_SSL_CACERT',
                61 => 'CURLE_BAD_CONTENT_ENCODING',
                62 => 'CURLE_LDAP_INVALID_URL',
                63 => 'CURLE_FILESIZE_EXCEEDED',
                64 => 'CURLE_USE_SSL_FAILED',
                65 => 'CURLE_SEND_FAIL_REWIND',
                66 => 'CURLE_SSL_ENGINE_INITFAILED',
                67 => 'CURLE_LOGIN_DENIED',
                68 => 'CURLE_TFTP_NOTFOUND',
                69 => 'CURLE_TFTP_PERM',
                70 => 'CURLE_REMOTE_DISK_FULL',
                71 => 'CURLE_TFTP_ILLEGAL',
                72 => 'CURLE_TFTP_UNKNOWNID',
                73 => 'CURLE_REMOTE_FILE_EXISTS',
                74 => 'CURLE_TFTP_NOSUCHUSER',
                75 => 'CURLE_CONV_FAILED',
                76 => 'CURLE_CONV_REQD',
                77 => 'CURLE_SSL_CACERT_BADFILE',
                78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
                79 => 'CURLE_SSH',
                80 => 'CURLE_SSL_SHUTDOWN_FAILED',
                81 => 'CURLE_AGAIN',
                82 => 'CURLE_SSL_CRL_BADFILE',
                83 => 'CURLE_SSL_ISSUER_ERROR',
                84 => 'CURLE_FTP_PRET_FAILED',
                85 => 'CURLE_RTSP_CSEQ_ERROR',
                86 => 'CURLE_RTSP_SESSION_ERROR',
                87 => 'CURLE_FTP_BAD_FILE_LIST',
                88 => 'CURLE_CHUNK_FAILED'
            ];
            return (!empty($curl_codes[$num])?$curl_codes[$num]:'Unknown');
        }

    }