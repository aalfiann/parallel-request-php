# ParallelRequest PHP

[![Version](https://img.shields.io/badge/stable-1.0.2-green.svg)](https://github.com/aalfiann/parallel-request-php)
[![Total Downloads](https://poser.pugx.org/aalfiann/parallel-request-php/downloads)](https://packagist.org/packages/aalfiann/parallel-request-php)
[![License](https://poser.pugx.org/aalfiann/parallel-request-php/license)](https://github.com/aalfiann/parallel-request-php/blob/HEAD/LICENSE.md)

A PHP class to create multiple request in parallel.

## Installation

Install this package via [Composer](https://getcomposer.org/).

1. For the first time project, you have to create the `composer.json` file, (skip to point 2, if you already have `composer.json`)  
```
composer init
```

2. Install
```
composer require "aalfiann/parallel-request-php:^1.0"
```

3. Done, for update in the future you can just run
```
composer update
```

## Usage send GET request

This will send GET request silently without response output.

```php
require_once ('vendor/autoload.php');
use \aalfiann\ParallelRequest;

$req = new ParallelRequest;
$req->request = ['https://jsonplaceholder.typicode.com/posts/1','https://jsonplaceholder.typicode.com/posts/2'];
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
require_once ('vendor/autoload.php');
use \aalfiann\ParallelRequest;

$req = new ParallelRequest;
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
require_once ('vendor/autoload.php');
use \aalfiann\ParallelRequest;
$req = new ParallelRequest;
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
require_once ('vendor/autoload.php');
use \aalfiann\ParallelRequest;
$req = new ParallelRequest;
echo $req->setRequest('http://jsonplaceholder.typicode.com/posts')->send()->getResponse();
```

## Function List
- **setRequest($request)** is to create the request url (string or array with post data).
- **setOptions($options=array())** is to set the options of CURLOPT.
- **setHttpStatusOnly($httpStatusOnly=false)** if set to true then output response will converted to http status code.
- **setDelayTime($time=10000)** is the delay execution time for cpu to take a rest. Default is 10000 (10ms) in microseconds.
- **setEncoded($encoded=true)** is to encode the data post. If you did not use this, the default data post is not encoded.
- **send()** is curl are sending the request (silently without any output)
- **getResponse()** is to get the output response (the return data could be string or array).
- **getResponseJson()** is to get the output response with json formatted.

**Note:**  
- If you only create single request, response will return string.
- This class basically using `curl_multi_exec()` function.
- If you not specify `$req->options` then it will be use `[CURLOPT_HEADER => false,CURLOPT_RETURNTRANSFER => true]` as default.