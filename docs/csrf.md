## CSRF Support

CSRF (Cross-Site Request Forgery) is an attack that tricks the victim into submitting a malicious request. It inherits
the identity and privileges of the victim to perform an undesired function on their behalf. For most web applications,
verifying the CSRF token is enough to protect against this kind of attack.

### How does it work?

We generate a CSRF token for each request. This token gets validated on each INERTIA request. If the token is invalid,
the request will be rejected, and we will return a 403 status code.

### Configuration

The values below are the default values for the CSRF configuration. You can change them in
your `config/packages/inertia.yaml` file.

```yaml
inertia:

  # The rest of your configuration here.....

  csrf:
    enabled: true
    # Be careful; when changing these values, they must match the values inside axios.
    # If you didn't mess with axios credential configuration, you can ignore cookie_name (xsrfCookieName) and header_name (xsrfHeaderName)
    cookie_name: XSRF-TOKEN
    header_name: X-XSRF-TOKEN

    # Cookie settings match the Cookie class. Except for: httpOnly which is always false and partialCookie which is always false.
    expire: 0
    path: /
    domain: null
    secure: false
    raw: false
    samesite: Lax
```

### Custom Error Message

You can customize the response in case of a CSRF token mismatch. To do this, you must create a new service
and overwrite the `@rompetomp_inertia.inertia_error_response` service inside your `config/services.yaml` file. Your
custom service must implement the `InertiaErrorResponseInterface` interface.

#### The configuration should look like this:

```yaml
services:

  # The rest of your services here.....

  # Your custom service
  rompetomp_inertia.inertia_error_response:
    class: App\Service\MyCustomInertiaErrorResponse

```

#### The contents of your service should look like this:

```php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
final class MyCustomInertiaErrorResponse implements DefaultInertiaErrorResponseInterface
{
    public function getResponse(): Response
    {
        return new Response('Something went wrong with Inertia!', 403);
    }
}
```
