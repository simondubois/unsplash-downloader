<?php namespace Simondubois\UnsplashDownloader;

/**
 * A proxy to handle history operations like :
 * - loading history from file
 * - checking existence of a string in history
 * - appending data to history
 * - saving history to file
 */
class History
{

    //
    // Attributes
    //

    /**
     * Has a file content been loaded
     * @var string
     */
    private $loaded = false;

    /**
     * Path of the file to use for history
     * @var string
     */
    private $path;

    /**
     * History content
     * @var array
     */
    private $content = [];



    //
    // File handling
    //

    /**
     * Read and parse file content
     * @param  string|null $path Path to file
     * @return bool True if the file has been successfully loaded
     */
    public function load($path) {
        if ($this->loaded) {
            throw new \Exception("The file {$this->path} has already been loaded into history");
        }

        $this->path = $path;
        if (is_file($this->path) === false) {
            return false;
        }

        if ($this->loadContent() === false) {
            return false;
        }

        $this->loaded = true;

        return true;
    }

    /**
     * Save content to file
     * @return bool True if the file has been successfully saved
     */
    public function save() {
        if ($this->loaded === false) {
            return false;
        }

        return $this->saveContent();
    }



    //
    // Content handling
    //

    /**
     * Check if a string is in history
     * @param  string  $str String to look for
     * @return bool True if the string has been found in history
     */
    public function has($str)
    {
        return $this->loaded && in_array($str, $content);
    }

    /**
     * Append a string to the history
     * @param  string $str [description]
     */
    public function put($str)
    {
        $this->content[] = $str;
    }

    /**
     * Load file content into content attribute
     * @return bool True if the file has been successfully loaded
     */
    private function loadContent() {
        $content = file($this->path, FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            return false;
        }

        $this->content = $content;

        return true;
    }

    /**
     * Save content attribute into file
     * @return bool True if the file has been successfully saved
     */
    private function saveContent() {
        $content = implode(PHP_EOL, $this->content);
        $writtenBytes = file_put_contents($this->path, $content);

        return is_int($writtenBytes);
    }

}
