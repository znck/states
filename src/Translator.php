<?php

namespace Znck\States;

use Illuminate\Support\Str;

class Translator
{
    /**
     * @var FileLoader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $fallbackLocale;

    /**
     * @var array
     */
    protected $loaded = [];

    /**
     * Translator constructor.
     *
     * @param FileLoader $loader
     * @param string     $locale
     */
    public function __construct(FileLoader $loader, string $locale)
    {
        $this->loader = $loader;
        $this->fallbackLocale = $locale;
    }

    /**
     * @param string $country
     * @param string $locale
     *
     * @return bool
     */
    protected function isLoaded(string $country, string $locale)
    {
        return isset($this->loaded[$country][$locale]);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    protected function parseKey(string $key)
    {
        return preg_split('/[ .]/', Str::upper($key));
    }

    /**
     * @param string      $key
     * @param string|null $locale
     *
     * @return string
     */
    public function get(string $key, string $locale = null)
    {
        list($country, $state) = $this->parseKey($key);

        $locale = $locale ?? $this->fallbackLocale;

        $this->load($country, $locale);

        if ($this->has($country, $locale, $state)) {
            return $this->loaded[$country][$locale][$state];
        }

        return $key;
    }

    /**
     * @param string      $key
     * @param string|null $locale
     *
     * @return string
     */
    public function getName(string $key, string $locale = null)
    {
        return $this->get($key, $locale);
    }

    /**
     * @param string $country
     * @param string $locale
     */
    protected function load(string $country, string $locale)
    {
        if ($this->isLoaded($country, $locale)) {
            return;
        }

        $this->loaded[$country][$locale] = $this->loader->load($country, $locale);
    }

    /**
     * @param string $country
     * @param string $state
     * @param string $locale
     *
     * @return bool
     */
    protected function has(string $country, string $locale, string $state)
    {
        return isset($this->loaded[$country][$locale][$state]);
    }
}
