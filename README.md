# ParallelRequest PHP

[![Version](https://img.shields.io/packagist/v/aalfiann/parallel-request-php.svg)](https://packagist.org/packages/aalfiann/parallel-request-php)
[![Downloads](https://img.shields.io/packagist/dt/aalfiann/parallel-request-php.svg)](https://packagist.org/packages/aalfiann/parallel-request-php)
[![License](https://img.shields.io/packagist/l/aalfiann/parallel-request-php.svg)](https://github.com/aalfiann/parallel-request-php/blob/HEAD/LICENSE.md)

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
    ->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'userId' => 3
    ],false)
    ->setOptions([
        CURLOPT_NOBODY => false,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ])
    ->setEncoded()->setHttpInfo()->send()->getResponse());
```

## How to debug
To send a request absolutely we need to know what happened in our request  
```php
// to see the detail of request
$req->httpInfo = 'detail';

// or use chain function like this
$req->setHttpInfo('detail')

// or if you want only http code info
$req->setHttpInfo()
```

## Difference setRequest() and addRequest() and addRequestRaw()
This three functions are required to build the request.  

`setRequest()` is need you to create the string / formatted array first before use this.  
Example:  
```php
// build the formatted array first.
// You are able to create more complex form-data or raw data.
$request = [
        'https://jsonplaceholder.typicode.com/posts/1',
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts/2',
            'post' => [
                'title' => 'foo 2',
                'body' => 'bar 2',
                'userId' => 2
            ]
        ],
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'post' => json_encode([
                'title' => 'foo 3',
                'body' => 'bar 3',
                'userId' => 3
            ])
        ]
    ];

// then you can use setRequest() function
$req->setRequest($request);
```

`addRequest()` is you can create string / formatted array on the fly  
Example:  
```php
// url only
$req->addRequest('https://jsonplaceholder.typicode.com/posts/1');

// send request with form-data
$req->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'title' => 'foo 2',
        'body' => 'bar 2',
        'userId' => 2
    ]);

// send request with data parameter on url
$req->addRequest('https://jsonplaceholder.typicode.com/posts',[
        'userId' => 3
    ],false);
```

`addRequestRaw()` is you can create raw data to send through request  
Example:  
```php
// url with raw json data
// setEncoded(true) or $req->encoded = true will not work, so you have to encoded this by yourself
$req->addRequestRaw('https://jsonplaceholder.typicode.com/posts',json_encode([
        'title' => 'foo 2',
        'body' => 'bar 2',
        'userId' => 2
    ]));

// http header is required to send raw json data
$req->options = [
    CURLOPT_NOBODY => false,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => ['Content-type: application/json; charset=UTF-8'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
];
```

## Function List
- **setRequest($request)** is to create the request url (string or array with post data).
- **addRequest($url,$params=array(),$formdata=true)** is to create the request url (string or array with params data).
- **addRequestRaw($url,$data)** is to create the request url with raw data formatted. Parameter data is not encoded by default.
- **setOptions($options=array())** is to set the options of CURLOPT.
- **setHttpStatusOnly($httpStatusOnly=false)** if set to true then output response will converted to http status code.
- **setHttpInfo($httpInfo=false)** if set to true then output response will display the http info status. Set to "detail" for more info.
- **setDelayTime($time=10000)** is the delay execution time for cpu to take a rest. Default is 10000 (10ms) in microseconds.
- **setEncoded($encoded=true)** is to encode the data post. The default data post is not encoded so you can create more complex data request.
- **send()** is curl are sending the request (silently without any output)
- **getResponse()** is to get the output response (the return data could be string or array).
- **getResponseJson()** is to get the output response with json formatted.

**Note:**  
- If you only create single request, response will return string.
- This class basically using `curl_multi_exec()` function.
- If you not specify `$req->options` then it will be use `[CURLOPT_HEADER => false,CURLOPT_RETURNTRANSFER => true]` as default.