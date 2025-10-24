# LaraHooks

[![Latest Version on Packagist](https://img.shields.io/packagist/v/realzone22/larahooks.svg?style=flat-square)](https://packagist.org/packages/realzone22/larahooks)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/realzone22/larahooks/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/realzone22/larahooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/realzone22/larahooks/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/realzone22/larahooks/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/realzone22/larahooks.svg?style=flat-square)](https://packagist.org/packages/realzone22/larahooks)

A lightweight, modular hook engine for Laravel that enables extensibility in both backend processes and Blade templates. Create flexible, plugin-like architectures with minimal overhead.

Based on [esemve/Hook](https://github.com/esemve/Hook) by Szalay Vince.

## Features

- 🎯 **Simple Hook Registration** - Register listeners with priorities
- 🔄 **Blade Directives** - Use `@shook` for inserting content and `@hook/@endhook` for wrapping/modifying content
- 🎨 **Content Modification** - Wrap and modify HTML sections
- 🧪 **Testing Support** - Mock hooks for easy testing
- 📊 **Hook Discovery** - List all registered and available hooks

## Installation

```shell
composer require realzone22/larahooks
```

Publish the configuration file (optional):

```shell
php artisan vendor:publish --tag="larahooks-config"
```

## Basic Usage

### Backend Hooks

Register a hook listener in your `AppServiceProvider`:

```php
use RealZone22\LaraHooks\Facades\LaraHooks;

public function boot(): void
{
    LaraHooks::listen('user.created', function ($callback, $output, $params) {
        $user = $params['user'];
        // Send welcome email, create profile, etc.
    }, 10);
}
```

Execute the hook in your code:
```php
use RealZone22\LaraHooks\Facades\LaraHooks;

$user = User::create($data);

LaraHooks::get('user.created', ['user' => $user]);
```

### Blade Template Hooks

#### Insertion Points with @shook

Use `@shook` to define insertion points where content can be injected:
```bladehtml
<div class="header">
    <h1>Welcome</h1>
    
    @shook('header.notifications')
</div>

<div class="sidebar">
    @shook('sidebar.widgets')
</div>
```

Inject content at these points:
```php
LaraHooks::listen('header.notifications', function ($callback, $output, $params) {
    return '<div class="notification">New messages!</div>';
}, 10);

LaraHooks::listen('sidebar.widgets', function ($callback, $output, $params) {
    return '<div class="weather-widget">Weather Widget</div>';
}, 10);
```

#### Content Wrapping with @hook/@endhook

Use `@hook` and `@endhook` to wrap content that can be modified or replaced:
```bladehtml
@hook('product.card')
    <div class="product">
        <h3>{{ $product->name }}</h3>
        <p>{{ $product->price }}</p>
    </div>
@endhook

@hook('footer.content')
    <div class="default-footer">
        <p>&copy; 2024 Company Name</p>
    </div>
@endhook
```

Modify or replace the wrapped content:
```php
LaraHooks::listen('product.card', function ($callback, $output, $params) {
    // Wrap existing content with additional markup
    return '<div class="featured-badge">Featured</div>' . $output;
}, 10);

LaraHooks::listen('footer.content', function ($callback, $output, $params) {
    // Replace entire content
    return '<div class="custom-footer"><p>Custom Footer Content</p></div>';
}, 10);
```

## Advanced Usage

### Hook Priorities

Lower numbers execute first:
```php
LaraHooks::listen('user.login', function () {
    // Runs first
}, 1);

LaraHooks::listen('user.login', function () {
    // Runs second
}, 10);

LaraHooks::listen('user.login', function () {
    // Runs third
}, 100);
```

### Stopping Hook Execution
```php
LaraHooks::listen('payment.process', function ($callback, $output, $params) {
    if ($params['amount'] > 1000) {
        LaraHooks::stop('payment.process');
        return 'Payment requires manual review';
    }
}, 1);

LaraHooks::listen('payment.process', function ($callback, $output, $params) {
    // This will not run if the amount > 1000
    return 'Payment processed successfully';
}, 10);
```

### Default Callbacks

Provide a fallback when no hooks are registered:
```php
$content = LaraHooks::get('custom.content', [], function () {
    return 'Default content when no hooks registered';
});
```

### Passing Parameters
```php
LaraHooks::get('order.created', [
    'order' => $order,
    'user' => $user,
    'total' => $total
]);
```

Access parameters in listeners:
```php
LaraHooks::listen('order.created', function ($callback, $output, $params) {
    $order = $params['order'];
    $user = $params['user'];
    $total = $params['total'];

    // Process order...
}, 10);
```

### Combining @shook and @hook

You can combine both directives for maximum flexibility:
```bladehtml
<div class="page-content">
    @shook('content.before')
    
    @hook('main.content')
        <article>
            <h1>{{ $title }}</h1>
            <p>{{ $content }}</p>
        </article>
    @endhook
    
    @shook('content.after')
</div>
```

Register listeners:
```php
// Insert content before main content
LaraHooks::listen('content.before', function ($callback, $output, $params) {
    return '<div class="breadcrumbs">Home > Article</div>';
}, 10);

// Wrap main content
LaraHooks::listen('main.content', function ($callback, $output, $params) {
    return '<div class="content-wrapper">' . $output . '</div>';
}, 10);

// Insert content after main content
LaraHooks::listen('content.after', function ($callback, $output, $params) {
    return '<div class="related-articles">Related Articles</div>';
}, 10);
```

## Testing

Mock hooks in your tests:
```php
use RealZone22\LaraHooks\Facades\LaraHooks;

public function test_user_creation_with_hooks(): void
{
    LaraHooks::mock('user.created', 'mocked response');

    $result = LaraHooks::get('user.created', ['user' => $user]);

    $this->assertEquals('mocked response', $result);
}
```

Run the test suite:
```shell
composer test
```

## Hook Discovery

List all registered hooks and their locations:
```shell
php artisan hook:list
```

This command shows:
- All registered hook listeners with priorities
- Hooks found in Blade templates (`@hook/@endhook`, `@shook`)
- Hooks called in PHP classes (`LaraHooks::get()`)

## Use Cases

### Plugin System
```php
// In a plugin's service provider
LaraHooks::listen('admin.menu', function ($callback, $output, $params) {
    return $output . '<li><a href="/plugin">My Plugin</a></li>';
}, 50);
```

### Theme Customization
```bladehtml
{{-- Base theme template --}}
@hook('footer.widgets')
    <div class="default-footer-widgets">
        <div class="widget">Default Widget</div>
    </div>
@endhook
```
```php
// In child theme service provider
LaraHooks::listen('footer.widgets', function ($callback, $output, $params) {
    return '<div class="custom-footer-widgets">Custom Widgets</div>';
}, 10);
```

### Dynamic Content Injection
```bladehtml
{{-- Template with injection point --}}
<div class="dashboard">
    <h1>Dashboard</h1>
    
    @shook('dashboard.widgets')
</div>
```
```php
// Multiple plugins can inject widgets
LaraHooks::listen('dashboard.widgets', function ($callback, $output, $params) {
    return '<div class="analytics-widget">Analytics</div>';
}, 10);

LaraHooks::listen('dashboard.widgets', function ($callback, $output, $params) {
    return $output . '<div class="stats-widget">Statistics</div>';
}, 20);
```

### Event-Driven Architecture
```php
LaraHooks::listen('invoice.paid', function ($callback, $output, $params) {
    // Update accounting system
}, 10);

LaraHooks::listen('invoice.paid', function ($callback, $output, $params) {
    // Send receipt email
}, 20);

LaraHooks::listen('invoice.paid', function ($callback, $output, $params) {
    // Update analytics
}, 30);
```

## API Reference

### LaraHooks Facade
```php
// Register a listener
LaraHooks::listen(string $hook, callable $callback, int $priority = 10): void

// Execute a hook
LaraHooks::get(string $hook, array $params = [], ?callable $callback = null, string $htmlContent = ''): mixed

// Stop hook execution
LaraHooks::stop(string $hook): void

// Mock hook for testing
LaraHooks::mock(string $hook, mixed $return): void

// Get all registered hooks
LaraHooks::getHooks(): array

// Get all listeners
LaraHooks::getListeners(): array

// Get listeners for specific hook
LaraHooks::getEvents(string $hook): array
```

### Blade Directives
```bladehtml
{{-- Insertion point - content will be injected here --}}
@shook('hook.name')

{{-- Content wrapper - content inside can be modified or replaced --}}
@hook('hook.name')
    Content that can be modified or replaced
@endhook
```

## Configuration

The package works out of the box without configuration. If you need to customize behavior, publish the config file and adjust as needed.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [RealZone22 | Lenny P.](https://github.com/RealZone22)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
