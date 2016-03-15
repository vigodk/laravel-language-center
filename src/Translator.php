<?php

namespace Novasa\LaravelLanguageCenter;

use Illuminate\Support\Arr;
use Illuminate\Translation\LoaderInterface;
use Novasa\LaravelLanguageCenter\ApiException;
use Illuminate\Translation\Translator as LaravelTranslator;

class Translator extends LaravelTranslator
{
	protected $languages = [];
	protected $strings = [];
	
    /**
     * Create a new translator instance.
     *
     * @param  \Illuminate\Translation\LoaderInterface  $loader
     * @param  string  $locale
     * @return void
     */
    public function __construct(LoaderInterface $loader, $locale)
    {
        $this->loader = $loader;
        $this->locale = $locale;
        
	    // Load langauges from API
	    if (count($this->languages) == 0) {
		    $this->loadLanguages();
		}
    }
	
    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
     */
    public function get($data, array $replace = [], $locale = null, $fallback = true, $created = false)
    {
	    // Make support for array data
	    if (is_array($data)) {
		    $key = $data['key'];
		    if (isset($data['string'])) {
			    $string = $data['string'];
		    }
		    if (isset($data['platform'])) {
			    $platform = $data['platform'];
		    }
		    if (isset($data['comment'])) {
			    $comment = $data['comment'];
		    }
	    } else {
		    $key = $data;
	    }
	    if (!isset($string)) {
		    $string = $key;
	    }
	    if (!isset($platform)) {
		    $platform = $this->getDefaultPlatform();
	    }
	    if (!isset($comment)) {
		    $comment = null;
	    }
	    
        list($namespace, $group, $item) = $this->parseKey($key);
        
        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        
        $locales = $fallback ? $this->parseLocale($locale) : [$locale ?: $this->locale];
        
        foreach ($locales as $locale) {
            $this->load($namespace, $group, $locale);
            
            $line = $this->getLine(
                $namespace, $group, $locale, $item, $replace, $platform
            );
            
            if (! is_null($line)) {
                break;
            }
        }
        
        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        
        if (! isset($line)) {
	        if (!$created) { 
				$this->createString($key, $string, $platform, $comment);
				return $this->get($data, $replace, $locale, $fallback, true);
			}
			
            return $key;
        }
        
        return $line;
    }
    
    /**
     * Retrieve a language line out the loaded array.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @param  array   $replace
     * @return string|array|null
     */
    protected function getLine($namespace, $group, $locale, $item, array $replace, $platform = null)
    {
	    if ($platform == null) {
		    $platform = $this->getDefaultPlatform();
	    }
	    
	    $this->loadLanguageStrings($locale, $platform);
	    
	    $key = implode('.', [$group, $item]);
	    if (isset($this->strings[$locale]) && isset($this->strings[$locale][$key])) {
		    return $this->makeReplacements($this->strings[$locale][$key], $replace);
	    }
	    
        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);
        
        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $line;
        }
    }
    
    protected function loadLanguageStrings($locale, $platform = null, $force = false)
    {
        // Load langauges from API
	    if (count($this->languages) == 0) {
		    $this->loadLanguages();
		}
	    
	    if ($platform == null) {
		    $platform = $this->getDefaultPlatform();
	    }
	    
	    if (in_array($locale, $this->languages)) {
		    if ($force OR !isset($this->strings[$locale])) {
			    $client = $this->getClient();
			    
				$res = $client->request('GET', $this->getApiUrl().'strings?platform='.$platform.'&language='.$locale, [
				    'auth' => $this->getAuthentication(),
				]);
				
				if ($res->getStatusCode() != 200) {
					throw new ApiException("API returned status [{$res->getStatusCode()}].");
				}
				
				$strings = json_decode((string)$res->getBody());
				
				$this->strings[$locale] = [];
				foreach ($strings as $string) {
					$this->strings[$locale][$string->key] = $string->value;
					$this->strings[$string->language][$string->key] = $string->value;
				}
		    }
	    }
    }
    
    protected function getClient()
    {
	    return new \GuzzleHttp\Client();
    }
    
    protected function getApiUrl() {
	    return \Config::get('languagecenter.url');
    }
    
    protected function getUsername()
    {
	    return \Config::get('languagecenter.username');
    }
    
    protected function getPassword()
    {
	    return \Config::get('languagecenter.password');
    }
    
    protected function getAuthentication() {
	    return [
	    	$this->getUsername(),
	    	$this->getPassword(),
	    ];
    }
    
    protected function loadLanguages()
    {
	    $client = $this->getClient();
	    
		$res = $client->request('GET', $this->getApiUrl().'languages', [
		    'auth' => $this->getAuthentication()
		]);
		
		if ($res->getStatusCode() != 200) {
			throw new ApiException("API returned status [{$res->getStatusCode()}].");
		}
		
		$languages = json_decode((string)$res->getBody());
		
		foreach ($languages as $language) {
			$this->languages[] = $language->codename;
			if ($language->is_fallback) {
				\Config::set('app.locale', $language->codename);
				\Config::set('app.fallback_locale', $language->codename);
				$this->locale = $language->codename;
			}
		}
    }
    
    protected function createString($key, $string, $platform = null, $comment = null)
    {
	    if ($platform == null) {
		    $platform = $this->getDefaultPlatform();
	    }
	    
	    $dotpos = strpos($key, '.');
	    
	    if (!($dotpos > 0)) {
		    throw new ApiException('Missing [.] in string key.');
	    }
	    
	    $category = str_replace(['_'], [' '], ucfirst(substr($key, 0, $dotpos)));
	    $name = str_replace(['_'], [' '], ucfirst(substr($key, $dotpos+1)));
	    
	    $client = $this->getClient();
	    
		$res = $client->request('POST', $this->getApiUrl().'string', [
		    'auth' => $this->getAuthentication(),
		    'form_params' => [
			    'platform' => $platform,
			    'category' => $category,
			    'key' => $name,
			    'value' => $string,
			    'comment' => $comment,
		    ],
		]);
		
		if ($res->getStatusCode() != 200) {
			throw new ApiException("API returned status [{$res->getStatusCode()}].");
		}
		
		foreach ($this->strings AS $locale => $strings) {
			$this->strings[$locale][$key] = $string;
		}
    }
    
    protected function getDefaultPlatform()
    {
	    return \Config::get('languagecenter.platform', 'web');
    }
	    
}
