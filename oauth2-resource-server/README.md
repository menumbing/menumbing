# Hyperf OAuth2 Resource Server

`menumbing/oauth2-resource-server` is a [Hyperf](https://hyperf.io) component that wraps 
the [PHP League OAuth2 Server](https://oauth2.thephpleague.com/) for **Resource Server** 
implementation and configuration.

This package simplifies the process of validating access tokens and protecting API endpoints
using the OAuth2 Resource Server specification.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Public Key](#public-key)
- [Usage](#usage)
  - [Authentication Guard Setup](#authentication-guard)
    - [User Guard](#user-guard)
    - [Client Guard](#client-guard)
  - [Provider](#provider)
    - [API Provider](#api-provider)
    - [Database Provider](#database-provider)
    - [Stateless Provider](#stateless-provider)

## Requirements

* PHP>=8.3
* swoole extension

## Installation

```
composer req menumbing/oauth2-resource-server

php bin/hyperf.php vendor:publish menumbing/oauth2-resource-server
```

After publishing package there should be the `oauth2-resource-server.php`
configuration file in the `config/autoload` folder.

## Configuration

### Public Key

To setup the public key required for an OAuth2 Resource Server to validate
access tokens, add the following environment variable below with either
the public key **file path** or **content**.

```
OAUTH2_PUBLIC_KEY=...
```

## Usage

### Authentication Guard

`menumbing/oauth2-resource-server` uses the [menumbing/auth](https://github.com/menumbing/auth) package 
that implements Laravel's auth system.

This package provides the two following guards to authenticate **User** and **Client** 
tokens. The following are the configurations for the guards.

#### User Guard

```php
'oauth2_user' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Guard\OAuth2UserGuard::class,
    'provider' => 'api_user',
    'options' => [
        'client_provider' => 'stateless',
        'access_token_provider' => 'stateless',
    ],
],
```

#### Client Guard
```php
'oauth2_client' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Guard\OAuth2ClientGuard::class,
    'provider' => 'stateless_client',
    'options' => [
        'access_token_provider' => 'stateless',
    ],
],
```

### Provider

This package has three data providers to retrieve User/Client data from the access token
received from incoming request. The following are the available providers:

- **API**: User/Client data is retrieved by requesting API to OAuth Server. Requires the `menumbing/http-client` package to be installed.
- **Database**: User/Client data is retrieved by connecting to OAuth Database. Requires the `hyperf/database` package to be installed.
- **Stateless**: User/Client data is retrieved from token payload.

### API Provider

#### User
```php
'api_user' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\User\ApiUserProvider::class,
    'options' => [
        'http_client' => 'oauth2',
    ],
],
```
#### Client
```php
'api_client' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\Client\ApiClientProvider::class,
    'options' => [
        'http_client' => 'oauth2',
    ],
],
```

### Database Provider

#### User
```php
'database_user' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\User\DatabaseUserProvider::class,
    'options' => [
        'connection' => 'oauth2',
    ],
],
```

#### Client
```php
'database_client' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\Client\DatabaseClientProvider::class,
    'options' => [
        'connection' => 'oauth2',
    ],
],
```

### Stateless Provider

#### User
```php
'stateless_user' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\User\StatelessUserProvider::class,
],
```

#### Client
```php
'stateless_client' => [
    'driver' => \Menumbing\OAuth2\ResourceServer\Provider\Client\StatelessClientProvider::class,
],
```