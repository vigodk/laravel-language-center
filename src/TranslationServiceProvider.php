<?php

namespace Novasa\LaravelLanguageCenter;

use Novasa\LaravelLanguageCenter\Translator;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();
		
	    $this->publishes([
	        __DIR__.'/../config/languagecenter.php' => config_path('languagecenter.php'),
	    ], 'config');
	}

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLoader();
        
        $this->app->singleton('translator', function ($app) {
	        
            $loader = $app['translation.loader'];
            
            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            
            $locale = $app['config']['app.locale'];
            
            $trans = new Translator($loader, $locale);
            
            $trans->setFallback($app['config']['app.fallback_locale']);
            
            return $trans;
        });
        
        $this->mergeConfigFrom(
		    __DIR__.'/../config/languagecenter.php', 'languagecenter'
		);
    }

}
