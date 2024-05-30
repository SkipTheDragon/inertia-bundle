## Making Inertia responses

To make an Inertia response, inject the `Rompetomp\InertiaBundle\Architecture\InertiaInterface $inertia` typehint in your
controller, and use the render function on that Service:

### Injecting the InertiaInterface in your controller class:

```php
<?php
namespace App\Controller;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
  public function __construct(private InertiaInterface $inertia)
  {
  }

  public function index(): Response
  {
    return $this->inertia->render('Dashboard', ['prop' => 'propValue']);
  }
}
```

### Injecting the InertiaInterface in your route:

```php
<?php
namespace App\Controller;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
  public function index(InertiaInterface $inertia): Response
  {
    return $inertia->render('Dashboard', ['prop' => 'propValue']);
  }
}
```

### Using InertiaTrait

```php
<?php
namespace App\Controller;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Rompetomp\InertiaBundle\Controller\InertiaTrait;

class DashboardController extends AbstractController
{
  use InertiaTrait;

  public function index(): Response
  {
    return $this->inertia->render('Dashboard', ['prop' => 'propValue']);
  }
}
```

## Sharing data

To share data with all your components, use `$inertia->share($key, $data)`. This can be done in an EventSubscriber:

```php
<?php

namespace App\EventSubscriber;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class InertiaSubscriber implements EventSubscriberInterface
{
  protected InertiaInterface $inertia;

  /**
   * AppSubscriber constructor.
   */
  public function __construct(InertiaInterface $inertia)
  {
    $this->inertia = $inertia;
  }

  public static function getSubscribedEvents()
  {
    return [
      KernelEvents::CONTROLLER => 'onControllerEvent',
    ];
  }

  public function onControllerEvent($event)
  {
    $this->inertia->share('Auth::user', [
      'name' => 'Hannes', // Synchronously
      'posts' => function () {
        return [1 => 'Post'];
      },
    ]);
  }
}
```

## View data

If you want to pass data to your root template, you can do that by passing a third parameter to the render function:

```php
return $inertia->render(
  'Dashboard',
  ['prop' => 'propValue'],
  ['title' => 'Page Title']
);
```

You can also pass these with the function `viewData`, just like you would pass data to the `share` function:

```php
$this->inertia->viewData('title', 'Page Title');
```

You can access this data in your layout file under the `viewData` variable.

## Asset versioning

Like in Laravel, you can also pass a version to the Inertia services by calling

```php
$inertia->version($version);
```

## Lazy Prop

It's more efficient to use lazy data evaluation server-side you are using partial reloads.

To use lazy data, you need to use `Rompetomp\InertiaBundle\Service\Inertia::lazy`

Sample usage:

```php
<?php
namespace App\Controller;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    public function index(InertiaInterface $inertia)
    {
        return $inertia->render('Dashboard', [
            // using array
            'usingArray' => $inertia->lazy(['SomeClass', 'someMethod']),
            // using string
            'usingString' => $inertia->lazy('SomeClass::someMethod'),
            // using callable
            'usingCallable' => $inertia->lazy(function () { return [...]; }),
        ]);
    }
}
```

The `lazy` method can accept callable, array and string
When using string or array; the service will try to check if it is an existing service in container if not
it will just proceed to call the function
