<?php
/**
 * Storage and retrieval of user uploads.
 *
 * @package Garp_File_Storage
 * @author David Spreekmeester <david@grrr.nl>
 */
interface Garp_File_Storage_Protocol {
    public function __construct(array $config, $path);

    /**
     * Overrides the components path, as opposed to the path provided in the constructor.
     *
     * @param string $path
     * @return void
     */
    public function setPath($path);

    public function exists($filename): bool;

    /**
     * Fetches the url to the file, suitable for public access on the web.
     *
     * @param string $filename
     * @return Garp_Util_AssetUrl
     */
    public function getUrl($filename): Garp_Util_AssetUrl;

    /**
     * Fetches the file data and returns resource.
     *
     * @param string $filename
     * @return string
     */
    public function fetch($filename): string;

    /**
     * Lists all valid files in the upload directory.
     *
     * @return array
     */
    public function getList(): array;

    /**
     * Returns mime type of given file.
     *
     * @param string $filename
     * @return string
     */
    public function getMime($filename): string;

    /**
     * Returns file size in bytes
     *
     * @param string $filename
     * @return float
     */
    public function getSize($filename): float;

    /**
     * Returns last modified time of file, as a Unix timestamp.
     *
     * @param string $filename
     * @return int
     */
    public function getTimestamp($filename): string;

    /**
     * @param string $filename
     * @param string $data
     * @param bool   $overwrite
     * @param bool   $formatFilename
     * @return string Destination filename.
     */
    public function store($filename, $data, $overwrite = false, $formatFilename = true): string;

    public function remove(string $filename);
}
