# Laravel Language Center
Language Center for Laravel.

> This requires access to Novasa's Language Center.

# Install

Run `composer require novasa/laravel-language-center:~1.0`.

In `config/app.php` replace `Illuminate\Translation\TranslationServiceProvider::class,` with `Novasa\LaravelLanguageCenter\TranslationServiceProvider::class,` in the `providers`-section.

Then add the the following environments to your application:
```
LANGUAGE_CENTER_URL=The url for API v1 (with ending slash) (required)
LANGUAGE_CENTER_USERNAME=Language Center username for Basic Auth (required)
LANGUAGE_CENTER_PASSWORD=Language Center password for Basic Auth (required)
LANGUAGE_CENTER_PLATFORM=The default platform to use. (default 'web')
LANGUAGE_CENTER_UPDATE_AFTER=The amount of seconds before updating the LanguageCenter data. (default '60' seconds)
```

For publishing the configuration file run:
```
php artisan vendor:publish --provider="Novasa\LaravelLanguageCenter\TranslationServiceProvider" --tag="config"
```

# Usage

You can use the Laravel standard `trans`-function.

```
trans('header.login');
```
> will return the value of the string id `header.login`.    
> However if the string id for `header.login` does not exists it will return `header.login`.

```
trans('header.welcome_back', [
  'username' => 'Mark',
])
```
> Will return the value of the string id `header.welcome_back`, but will replace `:username` with `Mark` in the translation.
> However if the string id for `header.welcome_back` does not exists it will return `header.welcome_back`.

```
trans([
  'key' => 'header.hello_world',
  'string' => 'Hello World!',
])
```
> Will return the value of the string id `header.hello_world`.
> However if the string id for `header.hello_world` does not exists it will return `Hello World!`.

```
trans([
  'key' => 'header.hello_user',
  'string' => 'Hello :username!',
], [
  'username' => 'Mark',
])
```
> Will return the value of the string id `header.hello_user`, but will replace `:username` with `Mark` in the translation.
> However if the string id for `header.hello_user` does not exists it will return `Hello Mark!`.

```
trans([
  'key' => 'header.download',
  'string' => 'You should download our iOS app!',
  'platform' => 'ios',
])
```
> Will return the value of the string id `header.download`, but for the platorm `ios`.
> However if the string id for `header.download` does not exists it will return `You should download our iOS app!`.

```
trans([
  'key' => 'footer.copyright',
  'string' => 'Copyright 2016 Novasa',
  'comment' => 'A comment that you would like to show at the Language Center.',
])
```
> Will return the value of the string id `footer.copyright`.
> However if the string id for `footer.copyright` does not exists it will return `Copyright 2016 Novasa`.
> This will also add a comment to the Language Center.

#### Note
If a translation does not exists it will automatically be created at the Language Center.

Also please note that the `locale` and `local_fallback` in the Laravel configurations are overwritten by the settings from the Language Center.
