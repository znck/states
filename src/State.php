<?php

namespace Znck\States;

/**
 * Class State.
 *
 * @property string $name
 * @property string $code
 */
trait State
{
    /**
     * @var string
     */
    protected static $locale = 'en';

    /**
     * @var Translator
     */
    protected static $states;

    /**
     * Boot city.
     */
    public static function bootCity()
    {
        static::$locale = config('app.locale', 'en');
        static::$states = app('translator.states');
    }

    /**
     * @param string $val
     *
     * @return string
     */
    public function getNameAttribute(string $val)
    {
        if (static::$locale === 'en') {
            return $val;
        }

        $name = static::$states->getName($this->code, static::$locale);

        if ($name === $this->code) {
            return $val;
        }

        return $name;
    }
}
