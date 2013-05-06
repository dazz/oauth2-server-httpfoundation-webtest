oauth2-server-httpfoundation-webtest
===================================

A webtesting bridge for oauth2-server-php with [bshaffer/oauth2-server-httpfoundation-bridge](https://github.com/bshaffer/oauth2-server-httpfoundation-bridge).

Silex, Symfony and other frameworks depending on symfony/http-kernel offer a WebTestCase class for testing controller/actions. In order to write webtests for actions that are secured by the bshaffer/oauth2-server-php the request instance needs to implement \OAuth2_RequestInterface.

To achieve a valid request object the WebTestCase classes need to extend the WebTestCase class provided here.

## Install

add to `composer.json`

```json
{
  "minimum-stability": "dev",
  "require-dev": {
    "dazz/oauth2-server-httpfoundation-webtest":"dev-master"
  }
}
```

Run `composer.phar update --dev` to install the development dependencies.

## Example for Silex

```php
namespace Company\Test\SomeBundle\Controller

use OAuth2\HttpFoundationWebTest\Silex\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testGetUser()
    {
        // create user, oauth2-client and access_token in the test-storage
        $accessToken = 'abc';
        // this creates the browsing client, not to mix up with the oauth2-client
        $client = $this->createClient();

        $client->request('GET', '/user/', array('access_token' => $accessToken));

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testProjects()
    {
        $accessToken = 'abc';
        $client = $this->createClient();

        $content = json_encode(array(
            'name' => 'My Project',
        ));

        // the content of $server['HTTP_AUTHORIZATION'] will be copied to the header
        $server = array(
            'HTTP_AUTHORIZATION'=> sprintf('Bearer %s', $accessToken),
        );

        $client->request('PUT', '/projects/theId', array(), array(), $server, $content);

        // all sorts of assertions on response string
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
```

## Links

* [oauth2-server-httpfoundation-bridge](https://github.com/bshaffer/oauth2-server-httpfoundation-bridge)
* [oauth2-server-php](https://github.com/bshaffer/oauth2-server-php)
* [Silex](http://silex.sensiolabs.org/doc/testing.html)
* [Symfony](http://symfony.com/doc/current/book/testing.html#your-first-functional-test)
