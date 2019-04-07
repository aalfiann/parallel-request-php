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
         * @return this
         */
        public function addRequest($url,$params=array()){
            if(!empty($params)){
                $this->request[] = [
                    'url' => $url,
                    'post' => $params
                ];
            } else {
                $this->request[] = $url;
            }
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
                        if ($this->encoded) {
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
                        if($this->httpInfo === 'detail'){
                            $result[$id] = [
                                'code' => curl_getinfo($c, CURLINFO_HTTP_CODE),
                                'info' => [
                                    'url' => curl_getinfo($c, CURLINFO_EFFECTIVE_URL),
                                    'content_type' => curl_getinfo($c, CURLINFO_CONTENT_TYPE),
                                    'content_length' => curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD),
                                    'total_time' => curl_getinfo($c, CURLINFO_TOTAL_TIME)
                                ],
                                'response' => curl_multi_getcontent($c)
                            ];
                        } else {
                            $result[$id] = [
                                'code' => curl_getinfo($c, CURLINFO_HTTP_CODE),
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
    }