<?php

class Garp_Content_Upload_Storage_Type_Bridge extends Garp_Content_Upload_Storage_Type_Abstract {

    /**
     * @var Garp_File_Storage_Protocol
     */
    protected $_service;

    public function __construct($environment) {
        parent::__construct($environment);

        $this->_service = initializeBridgeStorage();
    }

    public function fetchFileList() {
        $fileList           = new Garp_Content_Upload_FileList();
        $service            = $this->_getService();
        $uploadTypePaths    = $this->_getConfiguredPaths();

        foreach ($uploadTypePaths as $type => $dirPath) {
            $service->setPath($dirPath);
            $dirList        = $service->getList();
            $fileListByType = $this->_findFilesByType($dirList, $type);
            $fileList->addEntries($fileListByType);
        }

        return $fileList;
    }

    /**
     * Calculate the eTag of a file.
     * @param   string $filename    Filename
     * @param   string $type        File type, i.e. 'document' or 'image'
     * @return  string              Content hash (md5 sum of the content)
     */
    public function fetchEtag($filename, $type) {
        $relPath    = $this->_getRelPath($filename, $type);
        $dir        = $this->_getRelDir($relPath);
        $service    = $this->_getService();

        $service->setPath($dir);

        return $service->getEtag($filename);
    }


    /**
     * Fetches the contents of the given file.
     * @param   string $filename    Filename
     * @param   string $type        File type, i.e. 'document' or 'image'
     * @return  string              Content of the file. Throws an exception if file could not be read.
     */
    public function fetchData($filename, $type) {
        $relPath    = $this->_getRelPath($filename, $type);
        //return $this->_getService()->fetch($relPath);
        $ini        = $this->_getIni();
        $cdnDomain  = $ini->cdn->domain;
        $url        = 'http://' . $cdnDomain . $relPath;

        $content = @file_get_contents($url);
        if ($content === false) {
            throw new Exception("Could not read {$url} on " . $this->getEnvironment());
        }

        // Check for gzipped content
        $unpacked = gzdecode($content);
        $content = null !== $unpacked && false !== $unpacked ? $unpacked : $content;
        return $content;
    }


    /**
     * Stores given data in the file, overwriting the existing bytes if necessary.
     * @param   string $filename    Filename
     * @param   string $type        File type, i.e. 'document' or 'image'
     * @param   string &$data       File data to be stored.
     * @return  Boolean             Success of storage.
     */
    public function store($filename, $type, &$data) {
        $path       = $this->_getRelPath($filename, $type);
        $dir        = $this->_getRelDir($path);
        $service    = $this->_getService();

        $service->setPath($dir);
        return $service->store($filename, $data, true, false);
    }


    /**
     * @param   string $path    Relative path to the file.
     * @return  string          The relative path to the directory where the file resides.
     */
    protected function _getRelDir($path) {
        $filename = basename($path);
        $dir = substr($path, 0, strlen($path) - strlen($filename));
        return $dir;
    }


    protected function _getService(): Garp_File_Storage_Protocol {
        return $this->_service;
    }

    /**
     * @param array $dirList Array of file paths
     * @param string $type Upload type
     * @return Garp_Content_Upload_FileList
     */
    protected function _findFilesByType(array $dirList, $type) {
        $fileList = new Garp_Content_Upload_FileList();

        foreach ($dirList as $path) {
            if ($this->_isFilePath($path) && $this->_isAllowedPath($path)) {
                $baseName = basename($path);
                $fileNode = new Garp_Content_Upload_FileNode($baseName, $type);
                $fileList->addEntry($fileNode);
            }
        }

        return $fileList;
    }

    protected function _isFilePath($path) {
        return $path[strlen($path) - 1] !== '/';
    }

}
