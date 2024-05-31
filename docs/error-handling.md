## Error Handling

This page was heavily inspired by the [Inertia documentation](https://inertiajs.com/error-handling). Please read if
first to understand how you should set up the frontend, and refer to this page for Symfony-specific information.

### Development

One of the advantages to working with a robust server-side framework is the built-in exception handling you get for
free. For example, Symfony profiler, a beautiful error reporting tool which displays a nicely formatted stack
trace in local development.

The challenge is, if you're making an XHR request (which Inertia does) and you hit a server-side error, you're typically
left digging through the network tab in your browser's devtools to diagnose the problem.

Inertia solves this issue by showing all non-Inertia responses in a modal. This means you get the same beautiful
error-reporting you're accustomed to, even though you've made that request over XHR.

### Production

In production, you will want to return a proper Inertia error response instead of relying on the modal-driven error
reporting that is present during development. To accomplish this, you'll need to update your framework's default
exception handler to return a custom error page.

When building Symfony applications, you can accomplish this by using one of the following:

1. [Overriding the Default ErrorController](https://symfony.com/doc/current/controller/error_pages.html#overriding-the-default-errorcontroller)
2. [Working with the kernel.exception Event](https://symfony.com/doc/current/controller/error_pages.html#working-with-the-kernel-exception-event)

We will not get into the specifics of how to do this in Symfony, as it is well documented in the Symfony documentation.
But the general idea is to return an Inertia response pointing to your error page.

NOTE: By using the first method it's pretty straightforward, but if you choose the second method, you will need to edit the
Response object directly which offers you more flexibility but also introduces more complexity.
