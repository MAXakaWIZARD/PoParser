<?php

namespace PoParser;

class Entry
{
    /**
     * @var string
     */
    protected $msgId;

    /**
     * @var null
     */
    protected $msgIdPlural;

    /**
     * @var bool
     */
    protected $fuzzy = false;

    /**
     * @var bool
     */
    protected $obsolete = false;

    /**
     * @var array
     */
    protected $translations = array();

    /**
     * @param $properties
     */
    public function __construct($properties)
    {
        $this->msgId = $properties['msgid'];
        $this->msgIdPlural = isset($properties['msgid_plural']) ? $properties['msgid_plural'] : null;
        $this->fuzzy = !empty($properties['fuzzy']);
        $this->obsolete = !empty($properties['obsolete']);
        $this->translations = $properties['msgstr'];
    }

    /**
     * @return bool
     */
    public function isFuzzy()
    {
        return $this->fuzzy;
    }

    /**
     * @return string
     */
    public function getMsgId()
    {
        return is_array($this->msgId) ? implode('', $this->msgId) : $this->msgId;
    }

    /**
     * @return null|string
     */
    public function getMsgIdPlural()
    {
        return is_array($this->msgIdPlural) ? implode('', $this->msgIdPlural) : $this->msgIdPlural;
    }

    /**
     * @return bool
     */
    public function isObsolete()
    {
        return $this->obsolete;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param $index
     *
     * @return string
     */
    public function getTranslation($index = 0)
    {
        return (isset($this->translations[$index])) ? $this->translations[$index] : '';
    }

    /**
     * @return bool
     */
    public function isPlural()
    {
        return !empty($this->msgIdPlural);
    }
}
