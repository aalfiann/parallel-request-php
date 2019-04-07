# ParallelRequest PHP

[![Version](https://img.shields.io/badge/stable-1.2.0-green.svg)](https://github.com/aalfiann/parallel-request-php)
[![Total Downloads](https://poser.pugx.org/aalfiann/parallel-request-php/downloads)](https://packagist.org/packages/aalfiann/parallel-request-php)
[![License](https://poser.pugx.org/aalfiann/parallel-request-php/license)](https://github.com/aalfiann/parallel-request-php/blob/HEAD/LICENSE.md)

A PHP class to create multiple request in parallel (non blocking).

## Installation

Install this package via [Composer](https://getcomposer.org/).
```
composer require "aalfiann/parallel-request-php:^1.0"
```


## Usage send GET request

This will send GET request silently without response output.

```php
use \aalfiann\ParallelRequest;
require_once ('vendor/autoload.php');

$req = new ParallelRequest;

// You can set request with simple array like this
$req->request = ['https://jsonplaceholder.typicode.com/posts/1','https://jsonplaceholder.typicode.com/posts/2'];

// Or you can set request array with addRequest() function
for($i=1;$i<3;$i++){
    $req->addRequest('https://jsonplaceholder.typicode.com/posts/'.$i);
}

$req->options = [
    CURLOPT_NOBODY => false,
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
];
$req->send();
```

If you want to see the response
```php
echo var_dump($req->send()->getResponse());
```

## Usage send POST request

```php
use \aalfiann\ParallelRequest;
require_once ('vendor/autoload.php');

$req = new ParallelRequest;

// You can set post request with array formatted like this
$req->request = [
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'post' => [
                'title' => 'foo 1',
                'body' => 'bar 1',
                'userId' => 1
            ]
        ],
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'post' => [
                'title' => 'foo 2',
                'body' => 'bar 2',
                'userId' => 2
            ]
        ]
    ];

// Or you can set request array with addRequest() function
$req->setRequest(array()); //==> cleanup any request first (optional)
for($i=1;$i<3;$i++){
    $req->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'title' => 'foo '.$i,
        'body' => 'bar '.$i,
        'userId' => $i
    ]);
}

$req->encoded = true;
$req->options = [
    CURLOPT_NOBODY => false,
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
];

echo var_dump($req->send()->getResponse());
```

## Usage send for custom request
If you want to send custom request like PUT, PATCH, DELETE, etc.  
Just add the `CURLOPT_CUSTOMREQUEST => 'PUT'`.  
```php
use \aalfiann\ParallelRequest;
require_once ('vendor/autoload.php');

$req = new ParallelRequest;

// You can set post request with array formatted like this
$req->request = [
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts/1',
            'post' => [
                'id' => 1,
                'title' => 'foo 1',
                'body' => 'bar 1',
                'userId' => 1
            ]
        ],
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts/2',
            'post' => [
                'id' => 2,
                'title' => 'foo 2',
                'body' => 'bar 2',
                'userId' => 1
            ]
        ]
    ];

// Or you can set request array with addRequest() function
$req->setRequest(array()); //==> cleanup any request first (optional)
for($i=1;$i<3;$i++){
    $req->addRequest('https://jsonplaceholder.typicode.com/posts/'.$i,[
        'id' => $i,
        'title' => 'foo '.$i,
        'body' => 'bar '.$i,
        'userId' => $i
    ]);
}

$req->encoded = true;
$req->options = [
    CURLOPT_NOBODY => false,
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CUSTOMREQUEST => 'PUT'
];

echo var_dump($req->send()->getResponse());
```

## Chain Usage
You also able to make chain.
```php
use \aalfiann\ParallelRequest;
require_once ('vendor/autoload.php');

$req = new ParallelRequest;

// example simple request
echo $req->setRequest('http://jsonplaceholder.typicode.com/posts')->send()->getResponse();

// example complicated request
echo var_dump($req->
    ->setRequest(array()) //==> cleanup any request first (optional)
    ->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'title' => 'foo 1',
        'body' => 'bar 1',
        'userId' => 1
    ])
    ->addRequest('https://jsonplaceholder.typicode.com/posts/1')
    ->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'title' => 'foo 2',
        'body' => 'bar 2',
        'userId' => 2
    ])
    ->setOptions([
        CURLOPT_NOBODY => false,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ])
    ->setEncoded()->setHttpInfo()->send()->getResponse());
```

## Difference setRequest() and addRequest()
Both functions are required to build the request.  

`setRequest()` is need you to create the string / formatted array first before use this.  
Example:  
```php
// build the formatted array first
$request = [
        'https://jsonplaceholder.typicode.com/posts/1',
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts/2',
            'post' => [
                'title' => 'foo 2',
                'body' => 'bar 2',
                'userId' => 2
            ]
        ]
    ];

// then you can use setRequest() function
setRequest($request);
```

`addRequest()` is you can create string / formatted array on the fly  
Example:  
```php
addRequest('https://jsonplaceholder.typicode.com/posts/1');
addRequest('https://jsonplaceholder.typicode.com/posts',[
        'title' => 'foo 2',
        'body' => 'bar 2',
        'userId' => 2
    ]);
```

## Function List
- **setRequest($request)** is to create the request url (string or array with post data).
- **addRequest($url,$params=array())** is to create the request url (string or array with params data).
- **setOptions($options=array())** is to set the options of CURLOPT.
- **setHttpStatusOnly($httpStatusOnly=false)** if set to true then output response will converted to http status code.
- **setHttpInfo($httpInfo=false)** if set to true then output response will display the http info status. Set to "detail" for more info.
- **setDelayTime($time=10000)** is the delay execution time for cpu to take a rest. Default is 10000 (10ms) in microseconds.
- **setEncoded($encoded=true)** is to encode the data post. If you did not use this, the default data post is not encoded.
- **send()** is curl are sending the request (silently without any output)
- **getResponse()** is to get the output response (the return data could be string or array).
- **getResponseJson()** is to get the output response with json formatted.

**Note:**  
- If you only create single request, response will return string.
- This class basically using `curl_multi_exec()` function.
- If you not specify `$req->options` then it will be use `[CURLOPT_HEADER => false,CURLOPT_RETURNTRANSFER => true]` as default.